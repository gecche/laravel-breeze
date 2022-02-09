<?php

namespace Gecche\Breeze\Database\Console\Migrations;

use Illuminate\Support\Str;
use Illuminate\Support\Composer;
use Illuminate\Database\Migrations\MigrationCreator;

class MigrateMakeCommand extends \Illuminate\Database\Console\Migrations\MigrateMakeCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:migration {name : The name of the migration.}
        {--create= : The table to be created.}
        {--table= : The table to migrate.}
        {--path= : The location where the migration file should be created.}
        {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
        {--fullpath : Output the full path of the migration}
        {--timestamps=yes : Whether the migration table has timestamps (yes), no timestamps (no) or nullable timestamps (null) (only when creating, default=yes).}
        {--ownerships=no : Whether the migration table has ownerships (yes), no ownerships (no) or nullable ownerships (null) (only when creating, default=no).}';



    /**
     * Write the migration file to disk.
     *
     * @param  string  $name
     * @param  string  $table
     * @param  bool    $create
     * @return string
     */
    protected function writeMigration($name, $table, $create)
    {
        switch ($this->input->getOption('timestamps')) {
            case 'no':
                $timestamps = 'no';
                break;
            case 'null':
                $timestamps = 'nullable';
                break;
            default:
                $timestamps = 'yes';
                break;
        }
        switch ($this->input->getOption('ownerships')) {
            case 'yes':
                $ownerships = 'yes';
                break;
            case 'null':
                $ownerships = 'nullable';
                break;
            default:
                $ownerships = 'no';
                break;
        }

        $file = $this->creator->create(
            $name, $this->getMigrationPath(), $table, $create, $timestamps, $ownerships
        );

        if (! $this->option('fullpath')) {
            $file = pathinfo($file, PATHINFO_FILENAME);
        }

        $this->line("<info>Created Migration:</info> {$file}");
    }

}
