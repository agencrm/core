@include('vendor/autoload.php')

@setup
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $productionServer = $_ENV['DEPLOY_SERVER'];
    $repository = $_ENV['DEPLOY_REPOSITORY'];
    $baseDir = $_ENV['DEPLOY_BASE_DIR'];
    $branch = $_ENV['DEPLOY_BRANCH'];
    $phpFpmService = $_ENV['DEPLOY_PHP_FPM_SERVICE'];

    $releasesDir = $baseDir.'/releases';
    $sharedDir = $baseDir.'/shared';
    $currentDir = $baseDir.'/current';
    $release = date('YmdHis');
    $newReleaseDir = $releasesDir.'/'.$release;
@endsetup

@servers(['production' => $productionServer])

@task('setup', ['on' => 'production'])
    echo 'Setting up deployment directories...';

    # Create the base directories if they don't exist
    mkdir -p {{ $baseDir }}
    mkdir -p {{ $releasesDir }}
    mkdir -p {{ $sharedDir }}

    # Create shared directories
    mkdir -p {{ $sharedDir }}/storage/framework/{cache,views,sessions}
    mkdir -p {{ $sharedDir }}/bootstrap/cache

    # Ensure shared .env file exists
    if [ ! -f {{ $sharedDir }}/.env ]; then
        echo "Creating shared .env file";
        touch {{ $sharedDir }}/.env
        echo "Please update the shared .env file with your environment configuration.";
    fi

    # Ensure correct permissions
    echo "Setting permissions...";
    sudo chown -R deploy:www-data {{ $sharedDir }}
    find {{ $sharedDir }} -type d -exec chmod 775 {} \;
    find {{ $sharedDir }} -type f -exec chmod 664 {} \;

    echo 'Setup of deployment directories complete.';
@endtask



@task('fix-permissions', ['on' => 'production'])
    echo 'Fixing permissions...';

    # Set ownership to deploy:www-data
    sudo chown -R deploy:www-data {{ $baseDir }}

    # Set directory permissions to 775
    find {{ $baseDir }} -type d -exec chmod 775 {} \;

    # Set file permissions to 664
    find {{ $baseDir }} -type f -exec chmod 664 {} \;

    # Special permissions for executables
    chmod +x {{ $baseDir }}/shared/storage/framework
    chmod +x {{ $baseDir }}/shared/bootstrap/cache

    echo 'Permissions fixed.';
@endtask

@task('deploy', ['on' => 'production'])
    echo 'Deploying release {{ $release }} from branch {{ $branch }}...';

    # Clone the repository into the new release directory
    git clone -b {{ $branch }} "{{ $repository }}" "{{ $newReleaseDir }}"

    # Install dependencies
    cd "{{ $newReleaseDir }}"
    composer install --no-dev --optimize-autoloader

    # Source NVM and install Node.js dependencies
    echo 'Installing Node.js dependencies...';
    . ~/.bashrc && npm install

    # Build front-end assets
    echo 'Building front-end assets...';
    . ~/.bashrc && npm run build

    # Set up shared files and directories
    ln -nfs "{{ $sharedDir }}/.env" "{{ $newReleaseDir }}/.env"
    rm -rf "{{ $newReleaseDir }}/storage"
    ln -nfs "{{ $sharedDir }}/storage" "{{ $newReleaseDir }}/storage"
    rm -rf "{{ $newReleaseDir }}/bootstrap/cache"
    ln -nfs "{{ $sharedDir }}/bootstrap/cache" "{{ $newReleaseDir }}/bootstrap/cache"

    # Fix permissions for shared directories
    sudo chown -R deploy:www-data {{ $sharedDir }}/storage
    sudo chown -R deploy:www-data {{ $sharedDir }}/bootstrap/cache
    find {{ $sharedDir }}/storage -type d -exec chmod 775 {} \;
    find {{ $sharedDir }}/bootstrap/cache -type d -exec chmod 775 {} \;
    find {{ $sharedDir }}/storage -type f -exec chmod 664 {} \;
    find {{ $sharedDir }}/bootstrap/cache -type f -exec chmod 664 {} \

    # Migrate the database
    php artisan migrate --force

    # Update symlink to point to the new release
    echo "Updating current symlink to point to the new release...";
    if [ -e "{{ $currentDir }}" ]; then
        rm -rf "{{ $currentDir }}"
    fi
    ln -nfs "{{ $newReleaseDir }}" "{{ $currentDir }}"

    # Restart PHP-FPM
    echo "Restarting PHP-FPM service: {{ $phpFpmService }}...";
    sudo systemctl restart {{ $phpFpmService }}

    echo 'Deployment complete.';
@endtask


@task('cleanup', ['on' => 'production'])
    echo 'Cleaning up old releases...';

    # Keep only the last 5 releases
    cd "{{ $releasesDir }}"
    ls -dt */ | tail -n +6 | xargs rm -rf

    echo 'Cleanup complete.';
@endtask
