<?php
/**
 * Created by PhpStorm.
 * User: gecche
 * Date: 01/10/2019
 * Time: 11:15
 */

namespace Gecche\Breeze\Tests;

use Gecche\Breeze\Breeze;
use Illuminate\Suppport\Facades\Schema;
use Gecche\Breeze\Tests\App\Models\Author;
use Gecche\Breeze\Tests\App\Models\Book;
use Gecche\Breeze\Tests\App\Models\User;
use Gecche\Breeze\BreezeServiceProvider as ServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Gecche\Breeze\Tests\App\TestServiceProvider;

class BreezeOwnershipsTestCase extends \Orchestra\Testbench\TestCase
{

    protected $modelsNamespace;
    protected $modelsDir;
    protected $relationsDir;
    protected $models = [];

    use RefreshDatabase;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $appDir = __DIR__ . "/../app/";
        $this->modelsDir = $appDir . '/Models';
        $this->relationsDir = $this->modelsDir . '/Relations';
        $this->modelsNamespace = "Gecche\\Breeze\\Tests\\App\\Models";

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
//            'driver' => 'mysql',
//            'host' => '127.0.0.1',
//            'port' => '3306',
//            'database' => 'githubs',
//            'username' => 'homestead',
//            'password' => 'secret',
//            'unix_socket' => '',
//            'charset' => 'utf8',
//            'collation' => 'utf8_general_ci',
//            'prefix' => '',
//            'strict' => true,
//            'engine' => null,
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


    /*
     * In this test we check the use of ownerships within pivot
     * tables.
     *
     * The Book's "coauthors" relation uses the books_coauthors pivot
     * table. In its definition there are no added pivot fields.
     * So we test that both timestamps and ownerships fields are not
     * touched in the pivot table and they get null values.
     *
     * The Author's "coauthored" relation, instead, uses the same books_coauthors pivot
     * table but, in its definition, timestamps fields,
     * ownerships fields and a further "percentage" fields are handled
     * as added pivot fields.
     * So we test that when using that relation both timestamps and ownerships fields
     * are not updated accordingly.
     *
     */

    public function testCoauthorsOwnerships() {


        $this->artisan('migrate', ['--database' => 'testbench']);
        factory(User::class, 2)->create();

        //We log with user 1 and we create 3 authors and a book.
        $loggedUser = 1;
        Auth::loginUsingId($loggedUser);

        $author1 = Author::create([
            'code' => 'A00001',
            'name' => 'Dante',
            'surname' => 'Alighieri',
            'nation' => 'IT',
            'birthdate' => '1265-05-21',
        ]);

        $author2 = Author::create([
            'code' => 'A00002',
            'name' => 'Joanne Kathleen',
            'surname' => 'Rowling',
            'nation' => 'UK',
            'birthdate' => '1965-07-31',
        ]);

        $author3 = Author::create([
            'code' => 'A00003',
            'name' => 'Stephen',
            'surname' => 'King',
            'nation' => 'US',
            'birthdate' => '1947-09-21',
        ]);


        $book = Book::create([
            'title' => 'La divina commedia',
            'language' => 'IT',
            'author_id' => $author1->getKey(),
        ]);

        //We attach a coauthor ($author2) to the book using its relation
        //Initially we have no records for the book and $author2 in the
        //books_coauthors table
        $pivotRecord = DB::table('books_coauthors')
            ->where('book_id',$book->getKey())
            ->where('coauthor_id',$author2->getKey())
            ->first();

        $this->assertNull($pivotRecord);

        $book->coauthors()->attach([$author2->getKey()]);

        //At the end, we get a record in the pivot table
        //without timestamps nor ownerships.
        $pivotRecord = DB::table('books_coauthors')
            ->where('book_id',$book->getKey())
            ->where('coauthor_id',$author2->getKey())
            ->first();

        $this->assertNotNull($pivotRecord);
        $this->assertNull($pivotRecord->created_by);
        $this->assertNull($pivotRecord->updated_by);
        $this->assertNull($pivotRecord->created_at);
        $this->assertNull($pivotRecord->updated_at);



        //Now we want to attach another coauthor to the book
        //($author3) but using the Author's "coauthored" relation
        //Initially we have no records for the book and $author3 in the
        //books_coauthors table
        $pivotRecord = DB::table('books_coauthors')
            ->where('book_id',$book->getKey())
            ->where('coauthor_id',$author3->getKey())
            ->first();

        $this->assertNull($pivotRecord);

        //At the end, we get a record in the pivot table
        //with both timestamps and ownerships fields set
        //and the ownerships fields are filled with the currently
        //logged user 1.
        $author3->coauthored()->attach([$book->getKey()]);
        $pivotRecord = DB::table('books_coauthors')
            ->where('book_id',$book->getKey())
            ->where('coauthor_id',$author3->getKey())
            ->first();

        $this->assertNotNull($pivotRecord);
        $this->assertEquals($loggedUser,$pivotRecord->created_by);
        $this->assertEquals($loggedUser,$pivotRecord->updated_by);
        $this->assertNotNull($pivotRecord->created_at);
        $this->assertNotNull($pivotRecord->updated_at);


        //Finally, we log now with user 2 and, still using
        // Author's "coauthored" relation we update the
        //"percentage" field in the record linking the book to the
        // $author3 of the pivot table.
        //We use the "sync" method and at the end of the process
        //the "updated_by" ownerships field is filled with the value 2,
        //while the "created_by" field is remained as before.
        $newLoggedUser = 2;
        Auth::loginUsingId($newLoggedUser);
        $author3->coauthored()->sync([$book->getKey() => ['percentage' => 10]]);
        $pivotRecord = DB::table('books_coauthors')
            ->where('book_id',$book->getKey())
            ->where('coauthor_id',$author3->getKey())
            ->first();

        $this->assertEquals($loggedUser,$pivotRecord->created_by);
        $this->assertEquals($newLoggedUser,$pivotRecord->updated_by);

    }

}