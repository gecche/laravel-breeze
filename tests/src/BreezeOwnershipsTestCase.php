<?php
/**
 * Created by PhpStorm.
 * User: gecche
 * Date: 01/10/2019
 * Time: 11:15
 */

namespace Gecche\Breeze\Tests;

use Gecche\Breeze\Breeze;
use Gecche\Breeze\Facades\Schema;
use Gecche\Breeze\Tests\Models\Author;
use Gecche\Breeze\Tests\Models\Book;
use Gecche\Breeze\Tests\Models\User;
use Gecche\Breeze\BreezeServiceProvider as ServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BreezeOwnershipsTestCase extends \Orchestra\Testbench\TestCase
{

    protected $modelsNamespace;
    protected $modelsDir;
    protected $relationsDir;
    protected $models = [];

    //use RefreshDatabase;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        $this->modelsDir = __DIR__ . '/Models';
        $this->relationsDir = $this->modelsDir . '/Relations';
        $this->modelsNamespace = "Gecche\\Breeze\\Tests\\Models";

        $this->models = [
            'Book',
            'Author',
        ];

        parent::setUp();

        $this->withFactories(
            __DIR__ . '/../database/factories'
        );
//        app()->bind(AuthServiceProvider::class, function($app) { // not a service provider but the target of service provider
//            return new \Gecche\Breeze\Tests\AuthServiceProvider($app);
//        });

        $this->beforeApplicationDestroyed(function () {
            //$this->artisan('migrate:rollback');
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
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
//            'driver' => 'sqlite',
//            'database' => ':memory:',
//            'prefix' => '',
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'githubs',
            'username' => 'homestead',
            'password' => 'secret',
            'unix_socket' => '',
            'charset' => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
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
     * In this test we check the values of the created_by and updated_by Author's properties
     * First we authenticate with the User 1 and we check that, once a new author has been created,
     * it has 1 as value for both created_by and updated_by properties.
     *
     * Then we authenticate wiht User 2 and we update the model. The expected updated_by value is 2 and
     * the created_by should not be changed.
     *
     * Both during the creation and update of the author we don't explicitly set the ownerships values.
     */
//    public function testOwnershipsInAuthorsTableAuthenticatedUser()
//    {
//
//
//        $this->artisan('migrate', ['--database' => 'testbench']);
//
//
//        factory(User::class, 2)->create();
//
//        $author = new Author();
//
//        $this->assertTrue($author->ownerships);
//
//        Auth::loginUsingId(1);
//        //Since we are not authenticated, the created_by and updated_by columns should be set to null causing
//        // a db exception
//        $author = Author::create([
//            'code' => 'A00001',
//            'name' => 'Dante',
//            'surname' => 'Alighieri',
//            'nation' => 'IT',
//            'birthdate' => '1265-05-21',
//        ]);
//
//
//
//        $this->assertEquals($author->created_by,1);
//        $this->assertEquals($author->updated_by,1);
//
//
//        Auth::loginUsingId(2);
//
//
//        $author->name = 'Pippo';
//
//        $author->save();
//
//        $this->assertEquals($author->name,'Pippo');
//        $this->assertEquals($author->created_by,1);
//        $this->assertEquals($author->updated_by,2);
//
//
//    }



    public function testCoauthorsOwnerships() {


        $this->artisan('migrate', ['--database' => 'testbench']);
        //factory(User::class, 2)->create();

        Auth::loginUsingId(1);

//        Author::create([
//            'code' => 'A00001',
//            'name' => 'Dante',
//            'surname' => 'Alighieri',
//            'nation' => 'IT',
//            'birthdate' => '1265-05-21',
//        ]);
//
//        Author::create([
//            'code' => 'A00002',
//            'name' => 'Joanne Kathleen',
//            'surname' => 'Rowling',
//            'nation' => 'UK',
//            'birthdate' => '1965-07-31',
//        ]);
//
//        Author::create([
//            'code' => 'A00003',
//            'name' => 'Stephen',
//            'surname' => 'King',
//            'nation' => 'US',
//            'birthdate' => '1947-09-21',
//        ]);


        $book = Book::create([
            'title' => 'La divina commedia',
            'language' => 'IT',
            'author_id' => 1,
        ]);


        $book->coauthors()->attach([2,3]);


//        print_r($book->coauthors->toArray());

        $coauthorsNames = $book->coauthors->pluck('name')->toArray();
        $expectedCoauthorsNames = [
            'Joanne Kathleen',
            'Stephen',
        ];
        $this->assertEquals($expectedCoauthorsNames,$coauthorsNames);




    }

}