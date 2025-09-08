@include('vendor/autoload.php')

@setup
    // Load deployment environment (Envoy's own .env; not your app .env)
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $productionServer = $_ENV['DEPLOY_SERVER'];                // e.g. "deploy@192.168.0.118"
    $repository       = $_ENV['DEPLOY_REPOSITORY'];            // e.g. "git@github.com:you/agencrm.git"
    $baseDir          = rtrim($_ENV['DEPLOY_BASE_DIR'], '/');  // e.g. "/var/www/agencrm.app"
    $branch           = $_ENV['DEPLOY_BRANCH'] ?? 'main';
    $php              = $_ENV['DEPLOY_PHP'] ?? 'php';          // e.g. "php8.3"
    $phpFpmService    = $_ENV['DEPLOY_PHP_FPM_SERVICE'];       // e.g. "php8.3-fpm"
    $nodeVersion      = $_ENV['DEPLOY_NODE_VERSION'] ?? '';    // e.g. "20"
    $nvmDir           = $_ENV['DEPLOY_NVM_DIR'] ?? "$HOME/.nvm";
    $npmRunBuild      = $_ENV['DEPLOY_NPM_BUILD'] ?? 'build';

    $releasesDir   = $baseDir.'/releases';
    $sharedDir     = $baseDir.'/shared';
    $currentDir    = $baseDir.'/current';
    $release       = date('YmdHis');
    $newReleaseDir = $releasesDir.'/'.$release;

    // Helper strings
    $nvmLoad = "[ -s {$nvmDir}/nvm.sh ] && . {$nvmDir}/nvm.sh";
    $nodeSelect = $nodeVersion ? " && nvm use {$nodeVersion}" : "";
@endsetup

@servers(['production' => $productionServer])

@task('setup', ['on' => 'production'])
    echo 'Setting up deployment directories...';

    mkdir -p {{ $baseDir }} {{ $releasesDir }} {{ $sharedDir }}
    # Shared structure
    mkdir -p {{ $sharedDir }}/storage/framework/{cache,views,sessions}
    mkdir -p {{ $sharedDir }}/storage/app/public
    mkdir -p {{ $sharedDir }}/bootstrap/cache

    # Ensure shared .env exists (edit this after first setup)
    if [ ! -f {{ $sharedDir }}/.env ]; then
        echo "Creating empty shared .env at {{ $sharedDir }}/.env"
        touch {{ $sharedDir }}/.env
    fi

    # Ownership and safe default perms (web: www-data, deploy user: deploy)
    sudo chown -R deploy:www-data {{ $baseDir }}
    find {{ $baseDir }} -type d -exec chmod 775 {} \;
    find {{ $baseDir }} -type f -exec chmod 664 {} \;

    # Make storage & cache writable by web server
    sudo chown -R deploy:www-data {{ $sharedDir }}/storage {{ $sharedDir }}/bootstrap/cache
    find {{ $sharedDir }}/storage -type d -exec chmod 775 {} \;
    find {{ $sharedDir }}/storage -type f -exec chmod 664 {} \;
    find {{ $sharedDir }}/bootstrap/cache -type d -exec chmod 775 {} \;
    find {{ $sharedDir }}/bootstrap/cache -type f -exec chmod 664 {} \;

    echo 'Setup complete.';
@endtask

@task('deploy', ['on' => 'production'])
    set -e
    echo "Deploying release {{ $release }} from branch {{ $branch }}..."

    # 1) Fetch code for new release
    git clone -b {{ $branch }} --depth=1 "{{ $repository }}" "{{ $newReleaseDir }}"

    cd "{{ $newReleaseDir }}"

    # 2) Link shared files/dirs BEFORE installs so artisan can read env if needed
    ln -nfs "{{ $sharedDir }}/.env" "{{ $newReleaseDir }}/.env"
    rm -rf "{{ $newReleaseDir }}/storage"
    ln -nfs "{{ $sharedDir }}/storage" "{{ $newReleaseDir }}/storage"
    rm -rf "{{ $newReleaseDir }}/bootstrap/cache"
    ln -nfs "{{ $sharedDir }}/bootstrap/cache" "{{ $newReleaseDir }}/bootstrap/cache"

    # 3) PHP deps
    {{ $php }} -v
    composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

    # 4) Node build (via NVM if present)
    if {{ $nvmLoad }}; then
        echo "Using NVM at {{ $nvmDir }}{{ $nodeVersion ? ' (node ' . $nodeVersion . ')' : '' }}";
        {{ $nvmLoad }} {{ $nodeSelect }} && node -v && npm ci && npm run {{ $npmRunBuild }}
    else
        echo "NVM not found; using system node"
        node -v
        npm ci
        npm run {{ $npmRunBuild }}
    fi

    # 5) Laravel optimize / DB migrate (app stays live until symlink swap)
    {{ $php }} artisan storage:link || true
    {{ $php }} artisan config:cache
    {{ $php }} artisan route:cache
    {{ $php }} artisan view:cache
    {{ $php }} artisan event:cache

    {{ $php }} artisan migrate --force

    # 6) Atomic symlink swap
    echo "Switching current -> {{ $release }}"
    ln -nfs "{{ $newReleaseDir }}" "{{ $currentDir }}.new"
    mv -Tf "{{ $currentDir }}.new" "{{ $currentDir }}"

    # 7) Restart PHP-FPM
    echo "Restarting PHP-FPM: {{ $phpFpmService }}"
    sudo systemctl reload {{ $phpFpmService }} || sudo systemctl restart {{ $phpFpmService }}

    echo 'Deployment complete.'
@endtask

@task('cleanup', ['on' => 'production'])
    echo 'Cleaning up old releases...'
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
