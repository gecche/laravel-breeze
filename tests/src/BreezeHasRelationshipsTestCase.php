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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BreezeHasRelationshipsTestCase extends \Orchestra\Testbench\TestCase
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

        $this->artisan('migrate', ['--database' => 'testbench']);

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');
        });


        factory(User::class, 10)->create();

        Auth::loginUsingId(1);
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

        DB::table('books_coauthors')
            ->insert([
                'book_id' => 1,
                'coauthor_id' => 2,
            ]);
        DB::table('books_coauthors')
            ->insert([
                'book_id' => 1,
                'coauthor_id' => 3,
            ]);
        DB::table('books_coauthors')
            ->insert([
                'book_id' => 2,
                'coauthor_id' => 3,
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

        $app['config']->set('breeze',
            [
                'default-models-dir' => $this->modelsDir,
                'namespace' => $this->modelsNamespace,
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
            TestServiceProvider::class,
        ];
    }



    public function testBookRelations() {

        $book = Book::find(1);
        /*
         * Test if relation "author" has been instantiated properly
         */
        $author = $book->author;
        $expectedAuthorName = 'Dante';
        $this->assertEquals($expectedAuthorName,$author->name);


        /*
         * Test if relation "coauthors" has been instantiated properly
         */
        $coauthorsNames = $book->coauthors->pluck('name')->toArray();
        $expectedCoauthorsNames = [
            'Joanne Kathleen',
            'Stephen',
        ];
        $this->assertEquals($expectedCoauthorsNames,$coauthorsNames);
    }


    public function testAuthorRelations() {


        $author = Author::where('code','A00002')->first();
        /*
         * Test if relation "books" has been instantiated properly
         */
        $booksTitles = $author->books->pluck('title')->toArray();
        $expectedBooksTitles = [
            'Harry Potter and the Philosopher\'s Stone',
            'Harry Potter and the Chamber of Secrets',
            'Harry Potter adn the Prisoner fo Azkaban',
        ];
        $this->assertEquals($expectedBooksTitles,$booksTitles);

        /*
         * Test if relation "coauthored" has been instantiated properly
         */
        $coauthoredBooksTitles = $author->coauthored->pluck('title')->toArray();
        $expectedCoauthoredBooksTitles = [
            'La divina commedia',
        ];
        $this->assertEquals($expectedCoauthoredBooksTitles,$coauthoredBooksTitles);

    }

    /*
     * TEST #2 FOR COMPILE RELATIONS COMMAND
     * Compile relations for Gecche\Breeze\Tests\Models\Pippo
     * Check that Relations for class Pippo.php will not be compiled because Pippo is not a Model
     */
//    public function testCompileRelationsCommandPippo() {
//
//        $expectedArtisanOutput = 'Pippo.php not guessed as a model';
//        $expectedPippoRelationTraitFile = $this->relationsDir . '/PippoRelations.php';
//
//        $this->assertDirectoryNotExists($this->relationsDir);
//
//
//        $this->artisan('breeze:relations',['model' => 'Pippo']);
//
//        $output = Artisan::output();
//
//        $this->assertContains($expectedArtisanOutput,$output);
//        $this->assertDirectoryExists($this->relationsDir);
//        $this->assertFileNotExists($expectedPippoRelationTraitFile);
//    }


    protected function removeRelationTraitUse($filename,$modelName) {

        if (!File::exists($filename)) {
            return;
        }

        $fileContent = File::get($filename);

        $relationTraitUseString = "use Relations\\".$modelName."Relations;\n";

        $fileContent = str_replace($relationTraitUseString,'',$fileContent);

        File::put($filename,$fileContent);

    }

    protected function cleanRelations() {

        File::deleteDirectory($this->relationsDir);
        foreach ($this->models as $modelName) {
            $this->removeRelationTraitUse($this->modelsDir.'/'.$modelName.'.php',$modelName);
        }

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