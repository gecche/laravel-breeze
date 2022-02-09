<?php

namespace Gecche\Breeze\Database;

use Gecche\Breeze\Database\Console\Migrations\MigrateMakeCommand;
use Gecche\Breeze\Database\Migrations\MigrationCreator;

class MigrationServiceProvider extends \Illuminate\Database\MigrationServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;


    /**
     * Register the migration creator.
     *
     * @return void
     */
    protected function registerCreator()
    {
        $this->app->singleton('migration.creator', function ($app) {
            return new MigrationCreator($app['files'], $app->basePath('stubs'));
        });
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {

        $app = $this->app;

        $this->app->extend(\Illuminate\Database\Console\Migrations\MigrateMakeCommand::class, function () use ($app) {
            // Once we have the migration creator registered, we will create the command
            // and inject the creator. The creator is responsible for the actual file
            // creation of the migrations, and may be extended by these developers.
            $creator = $app['migration.creator'];

            $composer = $app['composer'];

            return new MigrateMakeCommand($creator,$composer);
        });


    }
//    /**
//     * Perform post-registration booting of services.
//     *
//     * @return void
//     */
//    /**
//     * Register the command.
//     *
//     * @return void
//     */
//    protected function registerMigrateMakeCommand()
//    {
//        $this->app->singleton(MigrateMakeCommand::class, function ($app) {
//            echo "hereMMC";
//            // Once we have the migration creator registered, we will create the command
//            // and inject the creator. The creator is responsible for the actual file
//            // creation of the migrations, and may be extended by these developers.
//            $creator = $app['migration.creator'];
//
//            $composer = $app['composer'];
//
//            return new MigrateMakeCommand($creator, $composer);
//        });
//    }

}
