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

    protected $migrationsToMake = [
        'create_pippo_table' => [
            '--create' => true,
            '--table' => 'pippo',
            '--ownerships' => 'yes',
            '--timestamps' => 'yes',
        ],
        'create_pippo2_table' => [
            '--create' => true,
            '--table' => 'pippo2',
            '--ownerships' => 'null',
            '--timestamps' => 'null',
        ],
        'create_pippo3_table' => [
            '--create' => true,
            '--table' => 'pippo3',
            '--ownerships' => 'no',
            '--timestamps' => 'no',
        ],
        'update_blank_table' => [

        ],
        'update_pippo_table' => [
            '--table' => 'pippo',
        ],
    ];


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

        parent::setUp();


        $this->beforeApplicationDestroyed(function () {
            $this->cleanMigrations();
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
     * We test that Breeze's migration:make command creates migration files accordingly to the various
     * options found
     *
     *
     */
    public function testMakeMigrationCommand()
    {


        $filesToCheck = $this->getMigrationFiles(array_keys($this->migrationsToMake));
        $this->assertEquals([], $filesToCheck);

        foreach ($this->migrationsToMake as $migrationName => $migrationParams) {
            $migrationParams = array_merge(['name' => $migrationName], $migrationParams);

            $this->artisan('make:migration', $migrationParams);
        }

        $filesToCheck = $this->getMigrationFiles(array_keys($this->migrationsToMake));
        $this->assertEquals(count($this->migrationsToMake), count($filesToCheck));

        $useStringsToCheck = [
            "use Gecche\\Breeze\\Facades\\Schema;",
            "use Gecche\\Breeze\\Database\\Schema\\Blueprint;",
        ];

        $timestampsStringsTocheck = [
            'null' => '$table->nullableTimestamps();',
            'no' => '$table->nullableTimestamps();',
        ];

        foreach ($this->migrationsToMake as $migrationName => $migrationParams) {

            $files = $this->getMigrationFiles($migrationName);

            $stringsToCheck = $useStringsToCheck;
            $stringsShouldNotContains = [];

            if (array_key_exists('--create', $migrationParams)) {
                $timestamps = Arr::get($migrationParams, '--timestamps', 'yes');
                switch ($timestamps) {
                    case 'no':
                        $stringsShouldNotContains[] = '$table->nullableTimestamps();';
                        $stringsShouldNotContains[] = '$table->timestamps();';
                        break;
                    case 'null':
                        $stringsShouldNotContains[] = '$table->timestamps();';
                        $stringsToCheck[] = '$table->nullableTimestamps();';
                        break;
                    default:
                        $stringsShouldNotContains[] = '$table->nullableTimestamps();';
                        $stringsToCheck[] = '$table->timestamps();';
                        break;
                }
                $ownerships = Arr::get($migrationParams, '--ownerships', 'no');
                switch ($ownerships) {
                    case 'no':
                        $stringsShouldNotContains[] = '$table->nullableOwnerships();';
                        $stringsShouldNotContains[] = '$table->ownerships();';
                        break;
                    case 'null':
                        $stringsShouldNotContains[] = '$table->ownerships();';
                        $stringsToCheck[] = '$table->nullableOwnerships();';
                        break;
                    default:
                        $stringsShouldNotContains[] = '$table->nullableOwnerships();';
                        $stringsToCheck[] = '$table->ownerships();';
                        break;
                }


            }

            foreach ($files as $file) {


                $fileContents = File::get($this->migrationsDir . '/' . $file);
                foreach ($stringsToCheck as $stringToCheck) {
                    $this->assertContains($stringToCheck, $fileContents);
                }
                foreach ($stringsShouldNotContains as $stringToCheck) {
                    $this->assertNotContains($stringToCheck, $fileContents);
                }
            }


        }

//        $this->artisan('breeze:relations',['model' => 'Author']);
//       print_r(Artisan::output());


    }


    protected function cleanMigrations()
    {

        $files = $this->getMigrationFiles(array_keys($this->migrationsToMake));

        foreach ($files as $filename) {
            File::delete($this->migrationsDir . '/' . $filename);
        }

    }


    protected function getMigrationFiles($migrationNames)
    {
        $migrationNames = Arr::wrap($migrationNames);


        $files = array_filter(File::files($this->migrationsDir), function ($file) use ($migrationNames) {

            $regEx = '/';

            foreach ($migrationNames as $migrationName) {
                $regEx .= '(.*)' . $migrationName . '\.php$|';
            }

            $regEx = rtrim($regEx, '|') . '/U';


            return preg_match($regEx, $file->getRelativePathname());
        });

        return array_map(function ($el) {
            return $el->getRelativePathname();
        }, $files);

    }


}