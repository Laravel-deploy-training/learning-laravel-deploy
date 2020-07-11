<?php
namespace Deployer;

require 'recipe/laravel.php';

// Project name
set('application', 'learning-laravel-deploy');

// Project repository
set('repository', 'git@github.com:Laravel-deploy-training/learning-laravel-deploy.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', false); 

// Thời gian tối đa để thực hiện 1 task deploy, quá thời gian sẽ fail.
set('default_timeout', 300);

// Shared files/dirs between deploys
add('shared_files', ['.env']);
add('shared_dirs', ['storage']);

// Writable dirs by web server
add('writable_dirs', [
    'bootstrap/cache',
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/logs',
]);

/**
 * npm task
 */
set('bin/npm', function () {
    return run('which npm');
});


// Hosts

host('project.com')
    ->set('deploy_path', '~/{{application}}');    

host('103.253.145.56')
	->user('deploy') 
	->stage('dev') 
	->set('deploy_path', '~/{{application}}');
// Tasks

task('build', function () {
    run('cd {{release_path}} && build');
});

task('reload:php-fpm', function () {
    run('sudo /usr/sbin/service php7.2-fpm reload');
});


desc('Install npm packages');
task('npm:install', function () {
    if (has('previous_release')) {
        if (test('[ -d {{previous_release}}/node_modules ]')) {
            run('cp -R {{previous_release}}/node_modules {{release_path}}');
        }
    }

    run('cd {{release_path}} && {{bin/npm}} install');
});

task('npm:run_dev', function () {
    run('cd {{release_path}} && {{bin/npm}} run dev');
});

task('deployer', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'npm:install',
    'npm:run_dev',
    'deploy:writable',
    'artisan:storage:link',
    'artisan:view:clear',
    'artisan:cache:clear',
    'artisan:config:cache',
    'artisan:optimize',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'reload:php-fpm',
]);

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.

before('deploy:symlink', 'artisan:migrate');

after('deployer', 'success');
