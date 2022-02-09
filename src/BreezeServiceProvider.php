<?php

namespace Gecche\Breeze;

use Gecche\Breeze\Console\CompileRelationsCommand;
use Gecche\Breeze\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as DBBuilder;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class BreezeServiceProvider extends ServiceProvider
{

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'CompileRelations',
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

        foreach ($this->commands as $command) {
            $this->{"register{$command}Command"}();
        }

        $this->commands(
            "command.breeze.relations"
        );

    }


    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerCompileRelationsCommand()
    {
        $this->app->singleton('command.breeze.relations', function () {
            return new CompileRelationsCommand;
        });
    }


    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {

        $this->publishes([
            __DIR__ . '/config/breeze.php' => config_path('breeze.php'),
        ]);

        Builder::macro('addUpdatedByColumn', function (array $values) {

            if (!$this->model->usesOwnerships()) {
                return $values;
            }

            return Arr::add(
                $values, $this->model->getUpdatedByColumn(),
                $this->model->currentUserId()
            );
        });

        Builder::macro('updateOwnerships', function (array $values) {
            return $this->toBase()->update($this->addUpdatedByColumn($this->addUpdatedAtColumn($values)));
        });

        Blueprint::macro('ownership', function ($column) {
            return $this->addColumn('integer', $column)->unsigned()->index();
        });

        Blueprint::macro('ownerships', function () {
            $this->ownership('created_by');

            $this->ownership('updated_by');
        });

        Blueprint::macro('nullableOwnerships', function () {
            $this->ownership('created_by')->nullable();

            $this->ownership('updated_by')->nullable();
        });

        Blueprint::macro('dropOwnerships', function ($column) {
            $this->dropColumn('created_by', 'updated_by');
        });

        Blueprint::macro('softDeletesOwnerships', function ($column = 'deleted_by') {
            return $this->integer($column)->unsigned()->nullable();
        });

    }

}
