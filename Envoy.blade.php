// Envoy.blade.php

@include('vendor/autoload.php')

@setup
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $productionServer = $_ENV['DEPLOY_SERVER'];
    $repository       = $_ENV['DEPLOY_REPOSITORY'];            // git@github.com:agencrm/core.git
    $baseDir          = rtrim($_ENV['DEPLOY_BASE_DIR'], '/');  // /var/www/agencrm.app
    $branch           = $_ENV['DEPLOY_BRANCH'] ?? 'main';
    $php              = $_ENV['DEPLOY_PHP'] ?? 'php';          // php8.3
    $phpFpmService    = $_ENV['DEPLOY_PHP_FPM_SERVICE'];       // php8.3-fpm
    $nodeVersion      = $_ENV['DEPLOY_NODE_VERSION'] ?? '';    // 20
    $nvmDir           = $_ENV['DEPLOY_NVM_DIR'] ?? "$HOME/.nvm";
    $npmRunBuild      = $_ENV['DEPLOY_NPM_BUILD'] ?? 'build';

    $releasesDir   = $baseDir.'/releases';
    $sharedDir     = $baseDir.'/shared';
    $currentDir    = $baseDir.'/current';
    $release       = date('YmdHis');
    $newReleaseDir = $releasesDir.'/'.$release;

    // Helper strings
    $nvmLoad    = "[ -s {$nvmDir}/nvm.sh ] && . {$nvmDir}/nvm.sh";
    $nodeSelect = $nodeVersion ? " && nvm use {$nodeVersion}" : "";
@endsetup

@servers(['production' => $productionServer])

@task('setup', ['on' => 'production'])
    set -e
    echo 'Setting up deployment directories...';

    mkdir -p {{ $baseDir }} {{ $releasesDir }} {{ $sharedDir }}
    mkdir -p {{ $sharedDir }}/storage/framework/{cache,views,sessions}
    mkdir -p {{ $sharedDir }}/storage/app/public
    mkdir -p {{ $sharedDir }}/bootstrap/cache

    # Shared SQLite: create directory and file if missing
    mkdir -p {{ $sharedDir }}/database
    if [ ! -f {{ $sharedDir }}/database/database.sqlite ]; then
        echo "Creating shared SQLite DB at {{ $sharedDir }}/database/database.sqlite"
        touch {{ $sharedDir }}/database/database.sqlite
    fi

    # Ensure shared .env exists
    if [ ! -f {{ $sharedDir }}/.env ]; then
        echo "Creating empty shared .env at {{ $sharedDir }}/.env"
        touch {{ $sharedDir }}/.env
    fi

    # Ownership & perms
    sudo chown -R deploy:www-data {{ $baseDir }}
    find {{ $baseDir }} -type d -exec chmod 775 {} \;
    find {{ $baseDir }} -type f -exec chmod 664 {} \;

    sudo chown -R deploy:www-data {{ $sharedDir }}/storage {{ $sharedDir }}/bootstrap/cache {{ $sharedDir }}/database
    find {{ $sharedDir }}/storage -type d -exec chmod 775 {} \;
    find {{ $sharedDir }}/storage -type f -exec chmod 664 {} \;
    find {{ $sharedDir }}/bootstrap/cache -type d -exec chmod 775 {} \;
    find {{ $sharedDir }}/bootstrap/cache -type f -exec chmod 664 {} \;
    chmod 664 {{ $sharedDir }}/database/database.sqlite || true

    echo 'Setup complete.';
@endtask

@task('deploy', ['on' => 'production'])
    # Fail fast, print line number, and on any error before the swap,
    # ensure 'current' points to the last completed release (not the new one).
    set -Eeuo pipefail

    on_error() {
        ec="$?"; line="$1";
        echo "ERROR at line ${line} (exit ${ec})."
        echo "Attempting to keep 'current' valid..."
        # If current is missing or broken, point it to last completed release (exclude the in-progress one).
        if [ ! -L "{{ $currentDir }}" ] && [ ! -d "{{ $currentDir }}" ]; then
            last_complete=$(ls -dt {{ $releasesDir }}/* 2>/dev/null | grep -v "{{ $release }}" | head -n1 || true)
            if [ -n "$last_complete" ]; then
                ln -nfs "$last_complete" "{{ $currentDir }}.new" && mv -Tf "{{ $currentDir }}.new" "{{ $currentDir }}"
                echo "Restored current -> $last_complete"
            else
                echo "No previous release to restore."
            fi
        fi
        exit "$ec"
    }
    trap 'on_error $LINENO' ERR

    echo "=== Starting deploy {{ $release }} (branch: {{ $branch }}) ==="

    echo "[1/7] Clone repo"
    git clone -b {{ $branch }} --depth=1 "{{ $repository }}" "{{ $newReleaseDir }}"

    cd "{{ $newReleaseDir }}"

    echo "[2/7] Link shared files/dirs"
    ln -nfs "{{ $sharedDir }}/.env" "{{ $newReleaseDir }}/.env"
    rm -rf "{{ $newReleaseDir }}/storage"
    ln -nfs "{{ $sharedDir }}/storage" "{{ $newReleaseDir }}/storage"
    rm -rf "{{ $newReleaseDir }}/bootstrap/cache"
    ln -nfs "{{ $sharedDir }}/bootstrap/cache" "{{ $newReleaseDir }}/bootstrap/cache"
    rm -f  "{{ $newReleaseDir }}/database/database.sqlite"
    ln -nfs "{{ $sharedDir }}/database/database.sqlite" "{{ $newReleaseDir }}/database/database.sqlite"

    echo "[3/7] Composer install"
    {{ $php }} -v
    composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

    echo "[4/7] Node build"
    if {{ $nvmLoad }}; then
        echo "Using NVM at {{ $nvmDir }}{{ $nodeVersion ? ' (node ' . $nodeVersion . ')' : '' }}"
        {{ $nvmLoad }} {{ $nodeSelect }} && node -v
    else
        echo "NVM not found; using system node (if available)"
        node -v || true
    fi
    if [ -f package-lock.json ]; then
        npm ci
    else
        echo "package-lock.json not found; using npm install"
        npm install
    fi
    npm run {{ $npmRunBuild }}

    echo "[5/7] Laravel caches & migrations"
    {{ $php }} artisan storage:link || true
    {{ $php }} artisan config:cache
    {{ $php }} artisan route:cache
    {{ $php }} artisan view:cache
    {{ $php }} artisan event:cache
    {{ $php }} artisan migrate --force

    echo "[6/7] Atomic symlink swap"
    ln -nfs "{{ $newReleaseDir }}" "{{ $currentDir }}.new"
    mv -Tf "{{ $currentDir }}.new" "{{ $currentDir }}"
    echo "current -> $(readlink -f {{ $currentDir }})"

    echo "[7/7] Reload PHP-FPM"
    sudo systemctl reload {{ $phpFpmService }} || sudo systemctl restart {{ $phpFpmService }}

    echo "=== Deploy {{ $release }} complete ==="
@endtask

@task('cleanup', ['on' => 'production'])
    set -e
    echo 'Cleaning up old releases (keep last 5)...'
    cd "{{ $releasesDir }}"
    ls -dt */ | tail -n +6 | xargs -r rm -rf
    echo 'Cleanup complete.'
@endtask

@task('rollback', ['on' => 'production'])
    set -e
    echo 'Rolling back to previous release...'
    cd "{{ $releasesDir }}"
    PREV=$(ls -dt */ | sed -n '2p' | tr -d '/')
    if [ -z "$PREV" ]; then
        echo "No previous release found"; exit 1;
    fi
    ln -nfs "{{ $releasesDir }}/$PREV" "{{ $currentDir }}.new"
    mv -Tf "{{ $currentDir }}.new" "{{ $currentDir }}"
    echo "Rolled back to $PREV"
    sudo systemctl reload {{ $phpFpmService }} || sudo systemctl restart {{ $phpFpmService }}
@endtask

@task('link-latest', ['on' => 'production'])
    set -e
    echo "Linking current -> latest release..."
    latest=$(ls -dt {{ $releasesDir }}/* 2>/dev/null | head -n1 || true)
    if [ -z "$latest" ]; then
        echo "No releases found in {{ $releasesDir }}"; exit 1;
    fi
    ln -nfs "$latest" "{{ $currentDir }}.new"
    mv -Tf "{{ $currentDir }}.new" "{{ $currentDir }}"
    echo "current -> $latest"
@endtask

@task('doctor', ['on' => 'production'])
    echo "Current symlink:"
    ls -l "{{ $currentDir }}" || echo "current missing"
    echo "Latest release:"
    ls -dt "{{ $releasesDir }}"/* | head -n1 || echo "no releases"
@endtask

@task('list-releases', ['on' => 'production'])
    echo 'Available releases:'
    ls -dt {{ $releasesDir }}/* | head -n 10
@endtask

@story('first-time')
    setup
@endstory

@story('ship')
    deploy
    cleanup
@endstory
