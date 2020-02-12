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

class BreezeMigrationsWithOwnershipsTestCase extends \Orchestra\Testbench\TestCase
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

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');
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
     * Test that migration's ownerhips fields are set in the table
     * and that they can be null
     *
     */
    public function testOwnershipsInUsersTable()
    {


        $this->artisan('migrate', ['--database' => 'testbench']);

        $hasColumns = Schema::hasColumns('users',['created_by','updated_by']);

        $this->assertTrue($hasColumns);

        factory(User::class, 1)->create();

        $user = User::find(1);

        $this->assertEquals($user->created_by,null);
        $this->assertEquals($user->updated_by,null);


    }

    /*
     * Test that migration's ownerhips fields are set in the table and
     * that they cannot be null
     *
     */
    public function testOwnershipsInAuthorsTableNotNullable()
    {

        $this->expectException(\PDOException::class);

        $this->artisan('migrate', ['--database' => 'testbench']);

        $hasColumns = Schema::hasColumns('authors',['created_by','updated_by']);

        $this->assertTrue($hasColumns);

        //Since we are not authenticated, the created_by and updated_by columns should be set to null causing
        // a db exception
        Author::create([
            'code' => 'A00001',
            'name' => 'Dante',
            'surname' => 'Alighieri',
            'nation' => 'IT',
            'birthdate' => '1265-05-21',
        ]);

//
//        Author::create([
//            'code' => 'A00002',
//            'name' => 'Joanne Kathleen',
//            'surname' => 'Rowling',
//            'nation' => 'UK',
//            'birthdate' => '1965-07-31',
//        ]);



    }

    /*
     * Test that migration's ownerhips fields are set in the table and
     * that they cannot be null
     *
     */
    public function testOwnershipsInAuthorsTableAuthenticatedUser()
    {


        $this->artisan('migrate', ['--database' => 'testbench']);


        factory(User::class, 2)->create();

        $author = new Author();

        $this->assertTrue($author->ownerships);

        Auth::loginUsingId(1);
        //Since we are not authenticated, the created_by and updated_by columns should be set to null causing
        // a db exception
        $author = Author::create([
            'code' => 'A00001',
            'name' => 'Dante',
            'surname' => 'Alighieri',
            'nation' => 'IT',
            'birthdate' => '1265-05-21',
        ]);



        $this->assertEquals($author->created_by,1);
        $this->assertEquals($author->updated_by,1);


        Auth::loginUsingId(2);


        $author->name = 'Pippo';

        $author->save();

        $this->assertEquals($author->name,'Pippo');
        $this->assertEquals($author->created_by,1);
        $this->assertEquals($author->updated_by,2);





    }



}