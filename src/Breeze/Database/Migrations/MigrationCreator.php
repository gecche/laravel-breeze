<?php

namespace Gecche\Breeze\Database\Migrations;

use Closure;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Filesystem\Filesystem;

class MigrationCreator extends \Illuminate\Database\Migrations\MigrationCreator
{

    /**
     * Create a new migration at the given path.
     *
     * @param  string  $name
     * @param  string  $path
     * @param  string  $table
     * @param  bool    $create
     * @return string
     * @throws \Exception
     */
    public function create($name, $path, $table = null, $create = false, $timestamps = 'yes', $ownerships = 'no')
    {
        $this->ensureMigrationDoesntAlreadyExist($name);

        // First we will get the stub file for the migration, which serves as a type
        // of template for the migration. Once we have those we will populate the
        // various place-holders, save the file, and run the post create event.
        $stub = $this->getStub($table, $create);

        $this->files->put(
            $path = $this->getPath($name, $path),
            $this->populateStub($name, $stub, $table, $timestamps, $ownerships)
        );

        // Next, we will fire any hooks that are supposed to fire after a migration is
        // created. Once that is done we'll be ready to return the full path to the
        // migration file so it can be used however it's needed by the developer.
        $this->firePostCreateHooks();

        return $path;
    }


    /**
     * Populate the place-holders in the migration stub.
     *
     * @param  string  $name
     * @param  string  $stub
     * @param  string  $table
     * @return string
     */
    protected function populateStub($name, $stub, $table, $timestamps = 'yes', $ownerships = 'no')
    {
        var_dump($timestamps);
        var_dump($ownerships);
        $stub = str_replace('DummyClass', $this->getClassName($name), $stub);

        // Here we will replace the table place-holders with the table specified by
        // the developer, which is useful for quickly creating a tables creation
        // or update migration from the console instead of typing it manually.
        if (! is_null($table)) {
            $stub = str_replace('DummyTable', $table, $stub);
        }

        switch ($timestamps) {
            case 'no':
                $stub = str_replace('$table->timestamps();', '', $stub);
                break;
            case 'nullable':
                $stub = str_replace('$table->timestamps()', '$table->nullableTimestamps()', $stub);
                break;
            default:
                break;
        }

        switch ($ownerships) {
            case 'no':
                $stub = str_replace('$table->ownerships();', '', $stub);
                break;
            case 'nullable':
                $stub = str_replace('$table->ownerships()', '$table->nullableOwnerships()', $stub);
                break;
            default:
                break;
        }

        return $stub;
    }

    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function stubPath()
    {
        return config('database.migrations-stub-path') ?: __DIR__.'/stubs';
    }
}
