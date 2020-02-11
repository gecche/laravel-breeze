<?php
/**
 * Created by PhpStorm.
 * User: gecche
 * Date: 01/10/2019
 * Time: 11:15
 */

namespace Gecche\Breeze\Tests;

use Gecche\Breeze\Breeze;
use Gecche\Breeze\Tests\ModelsForCompiling\Author;
use Gecche\Breeze\Tests\ModelsForCompiling\Book;

use Gecche\Breeze\BreezeServiceProvider as ServiceProvider;
use Gecche\Breeze\Database\MigrationServiceProvider as MigrationServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BreezeMakeMigrationCommandTestCase extends \Orchestra\Testbench\TestCase
{



    protected $migrationsDir;
    protected $laravelAppPath;

    protected $migrationNames;



    use RefreshDatabase;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {

        $this->laravelAppPath = __DIR__ . '/../../vendor/orchestra/testbench-core/laravel';
        $this->migrationsDir = $this->laravelAppPath . '/database/migrations';

        $this->migrationNames = [
            'create_pippo_table',
            'create_pippo2_table',
        ];

        parent::setUp();


        //$this->cleanMigrations();
        $this->beforeApplicationDestroyed(function () {
            //$this->cleanMigrations();
        });

    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // set up database configuration
        $app['config']->set('breeze',
            [
//                'default-models-dir' => $this->modelsDir,
//                'namespace' => $this->modelsNamespace,
            ]
        );

    }

    /**
     * Get Sluggable package providers.
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
            MigrationServiceProvider::class,
            TestServiceProvider::class,
        ];
    }


    /*
     * TEST #1 FOR MAKE MIGRATION COMMAND
     *
     */
    public function testMakeMigrationCommand() {


        $filesToCheck = $this->getMigrationFiles($this->migrationNames);
        $this->assertEquals([],$filesToCheck);

        foreach ($this->migrationNames as $migrationName) {
            $this->artisan('make:migration',
                [
                    'name' => $migrationName,
                    '--table' => 'pippo',
                    '--ownerships' => 'yes',
                    '--timestamps' => 'null',
                    '--create' => true,
                ]
            );
        }

        $filesToCheck = $this->getMigrationFiles($this->migrationNames);
        $this->assertEquals(count($this->migrationNames),count($filesToCheck));

//        $this->artisan('breeze:relations',['model' => 'Author']);
//       print_r(Artisan::output());


    }




    protected function removeRelationTraitUse($filename,$modelName) {

        if (!File::exists($filename)) {
            return;
        }

        $fileContent = File::get($filename);

        $relationTraitUseString = "\n\t"."use Relations\\".$modelName."Relations;\n";

        $fileContent = str_replace($relationTraitUseString,'',$fileContent);

        File::put($filename,$fileContent);

    }

    protected function cleanMigrations() {

        $files = $this->getMigrationFiles($this->migrationNames);

        foreach ($files as $filename) {
            File::delete($this->migrationsDir . '/' . $filename);
        }

    }


    protected function getMigrationFiles($suffixes) {
        $suffixes = Arr::wrap($suffixes);


        $files = array_filter(File::files($this->migrationsDir),function ($file) use ($suffixes) {

            $regEx = '/';

            foreach ($this->migrationNames as $migrationName) {
                $regEx .= '(.*)'.$migrationName.'\.php$|';
            }

            $regEx = rtrim($regEx,'|').'/U';


            return preg_match($regEx, $file->getRelativePathname());
        });

        return array_map(function ($el) {
            return $el->getRelativePathname();
        },$files);

    }


}