<?php
/**
 * Created by PhpStorm.
 * User: gecche
 * Date: 01/10/2019
 * Time: 11:15
 */

namespace Gecche\Breeze\Tests;

use Gecche\Breeze\Breeze;
use Gecche\Breeze\Tests\Models\Author;
use Gecche\Breeze\Tests\Models\Book;
use Gecche\Breeze\Tests\Models\User;
use Gecche\Breeze\BreezeServiceProvider as ServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BreezeHasRelationshipsTestCase extends \Orchestra\Testbench\TestCase
{

    use RefreshDatabase;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->withFactories(
            __DIR__ . '/../database/factories'
        );
//        app()->bind(AuthServiceProvider::class, function($app) { // not a service provider but the target of service provider
//            return new \Gecche\Breeze\Tests\AuthServiceProvider($app);
//        });

        $this->artisan('migrate', ['--database' => 'testbench']);

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');
        });


        factory(User::class, 10)->create();

        Author::create([
            'code' => 'A00001',
            'name' => 'Dante',
            'surname' => 'Alighieri',
            'nation' => 'IT',
            'birthdate' => '1265-05-21',
        ]);

        Author::create([
            'code' => 'A00002',
            'name' => 'Joanne Kathleen',
            'surname' => 'Rowling',
            'nation' => 'UK',
            'birthdate' => '1965-07-31',
        ]);

        Author::create([
            'code' => 'A00003',
            'name' => 'Stephen',
            'surname' => 'King',
            'nation' => 'US',
            'birthdate' => '1947-09-21',
        ]);

        Author::create([
            'code' => 'A00004',
            'name' => 'Ken',
            'surname' => 'Follett',
            'nation' => 'UK',
            'birthdate' => '1949-06-05',
        ]);


        Book::create([
            'title' => 'La divina commedia',
            'language' => 'IT',
            'author_id' => 1,
        ]);

        Book::create([
            'title' => 'Fall of giants',
            'language' => 'EN',
            'author_id' => 4,
        ]);

        Book::create([
            'title' => 'The Pillars of the Earth',
            'language' => 'EN',
            'author_id' => 4,
        ]);

        Book::create([
            'title' => 'Misery',
            'language' => 'EN',
            'author_id' => 3,
        ]);

        Book::create([
            'title' => 'Harry Potter and the Philosopher\'s Stone',
            'language' => 'EN',
            'author_id' => 2,
        ]);

        Book::create([
            'title' => 'Harry Potter and the Chamber of Secrets',
            'language' => 'EN',
            'author_id' => 2,
        ]);

        Book::create([
            'title' => 'Harry Potter adn the Prisoner fo Azkaban',
            'language' => 'EN',
            'author_id' => 2,
        ]);
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
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth.providers', [
            'users' => [
                'driver' => 'eloquent',
                'model' => User::class,
            ]
        ]);
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
            TestServiceProvider::class,
        ];
    }


    /*
     * TEST FOR DOMAIN ADD COMMAND
     * It checks if the env file and storage dirs exist and if the list of domains in the config file is updated
     */
    public function testCompileRelationsCommand() {

        Config::set('breeze',['default-models-dir' => __DIR__ .'/Models','namespace' => "Gecche\\Breeze\\Tests\\Models\\"]);
        $this->artisan('breeze:relations',['model' => 'Book']);
        print_r(Artisan::output());

//        $this->assertFileExists(base_path('.env.'.$site));
//
//        $this->artisan('config:clear');
//
//        $domainListed = Config::get('domain.domains');
//
//        $this->assertArrayHasKey($site,$domainListed);
//
//        $this->assertDirectoryExists(storage_path(domain_sanitized($site)));
    }

    /*
    * Test HasValidation validate method without building exclusion rules and checking the
     * validation error bag
    */
//    public function testValidateMethodExceptionError()
//    {
//
//
//        $author = Author::find(1);
//
//        /*
//          * We expect exception is thrown because the model with id 1
//          * has the code A000001 and it is involved in the unique rule
//          */
//
//        $author->code = ['A00001'];
//
//        try {
//            $author->validate(false);
//
//        } catch (ValidationException $e) {
//
//            $errorsExpected = [
//                'code' => [
//                    'The code has already been taken.',
//                ],
//            ];
//
//            $this->assertEquals($e->errors(), $errorsExpected);
//        }
//
//
//    }
//


}