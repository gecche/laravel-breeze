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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BreezeHasValidationTestCase extends \Orchestra\Testbench\TestCase
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
     * Test HasValidation getModelValidationSettings
     */
    public function testGetModelValidationSettingsMethod()
    {

        $author = Author::find(1);

        /*
         * We expect the validation data with the unique rule instantied with the id of the model
         */

        $validationData = $author->getModelValidationSettings();

        $expectedValidationData = [
            'rules' => [
                'surname' => ['required'],
                'code' => ['required','unique:authors,code,1,id'],
            ],
            'customMessages' => [],
            'customAttributes' => [],
        ];
        $this->assertEquals($validationData, $expectedValidationData);

        /*
         * We expect the validation data as is in the model class
         * No building instantiated unique rules
         */

        $validationData = $author->getModelValidationSettings(false);

        $expectedValidationData = [
            'rules' => [
                'surname' => 'required',
                'code' => 'required|unique:authors,code',
            ],
            'customMessages' => [],
            'customAttributes' => [],
        ];
        $this->assertEquals($validationData, $expectedValidationData);
    }

    /*
     * Test HasValidation getModelValidationSettings with context
     */
    public function testGetModelValidationSettingsMethodWithContext()
    {

        $author = Author::find(1);

        /*
         * We expect the validation data with the unique rule instantied with the id of the model
         */

        $validationData = $author->getModelValidationSettings(true, 'insert');

        $expectedValidationData = [
            'rules' => [
                'surname' => ['required'],
                'code' => ['required','unique:authors,code,1,id'],
            ],
            'customMessages' => [
                'surname.required' => 'you must insert an author with a surname',
            ],
            'customAttributes' => [],
        ];
        $this->assertEquals($validationData, $expectedValidationData);

        /*
         * We expect the validation data as is in the model class
         * No building instantiated unique rules
         */

        $validationData = $author->getModelValidationSettings(true, 'edit');

        $expectedValidationData = [
            'rules' => [
                'surname' => ['required'],
                'code' => ['required','unique:authors,code,1,id'],
                'birthdate' => ['required'],
            ],
            'customMessages' => [
                'surname.required' => 'ok, at least now you have to set a surname!',
            ],
            'customAttributes' => [],
        ];
        $this->assertEquals($validationData, $expectedValidationData);
    }


    /*
    * Test HasValidation getModelValidationSettings with merge of rules
    */
    public function testGetModelValidationSettingsMethodWithMergeOfRules()
    {

        $author = Author::find(1);

        /*
         * We expect the validation data with the unique rule instantied with the id of the model
         */

        $validationData = $author->getModelValidationSettings(true, null, ['name' => 'required']);

        $expectedValidationData = [
            'rules' => [
                'surname' => ['required'],
                'code' => ['required','unique:authors,code,1,id'],
                'name' => ['required'],
            ],
            'customMessages' => [],
            'customAttributes' => [],
        ];
        $this->assertEquals($validationData, $expectedValidationData);

        /*
         * We expect the validation data with the unique rule instantied with the id of the model
         */

        $validationData = $author->getModelValidationSettings(true, 'insert', ['name' => 'required']);

        $expectedValidationData = [
            'rules' => [
                'surname' => ['required'],
                'code' => ['required','unique:authors,code,1,id'],
                'name' => ['required'],
            ],
            'customMessages' => [
                'surname.required' => 'you must insert an author with a surname',
            ],
            'customAttributes' => [],
        ];
        $this->assertEquals($validationData, $expectedValidationData);
    }


    /*
    * Test HasValidation getModelValidationSettings with merge of rules
    */
    public function testGetValidatorMethod1()
    {

        $author = Author::find(1);

        /*
         * We expect the validation data with the unique rule instantied with the id of the model
         */

        $author->code = ['A00002'];

        $validator = $author->getValidator();

        $expectedValidationData = [
            'rules' => [
                'surname' => ['required'],
                'code' => ['required','unique:authors,code,1,id'],
            ],
            'customMessages' => [],
            'customAttributes' => [],
        ];

        $validatorRules = $validator->getRules();
        $this->assertEquals($validatorRules, $expectedValidationData['rules']);

        $validator->passes();

        $errorsExpected = [
            'code' => [
                'The code has already been taken.',
            ],
        ];

        $this->assertEquals($validator->errors()->toArray(), $errorsExpected);

    }

    /*
    * Test HasValidation getModelValidationSettings with merge of rules
    */
    public function testGetValidatorMethod2()
    {

        $author = Author::find(1);

        /*
         * We expect the validation data with the unique rule instantied with the id of the model
         */

        $author->code = ['A00002'];

        $codeUniqueMessage = 'Oh no, an author with this code is already born.';
        $customMessages = ['code.unique' => $codeUniqueMessage];

        $validator = $author->getValidator(null,true,null,[],$customMessages);

        $expectedValidationData = [
            'rules' => [
                'surname' => ['required'],
                'code' => ['required','unique:authors,code,1,id'],
            ],
            'customMessages' => $customMessages,
            'customAttributes' => [],
        ];

        $validatorRules = $validator->getRules();
        $this->assertEquals($validatorRules, $expectedValidationData['rules']);

        $validator->passes();

        $errorsExpected = [
            'code' => [
                $codeUniqueMessage,
            ],
        ];

        $this->assertEquals($validator->errors()->toArray(), $errorsExpected);

    }

    /*
    * Test HasValidation validate method with building exclusion rules
    */
    public function testValidateMethodNoException()
    {

        $author = Author::find(1);

        /*
         * We expect no exception because the model with id 1
         * has the code A000001, but it is magically excluded
         * within the unique rule
         */

        $author->code = ['A00001'];

        $author->validate(true);

        $this->assertInstanceOf(Breeze::class,$author);


    }

    /*
    * Test HasValidation validate method without building exclusion rules
    */
    public function testValidateMethodException()
    {

        $this->expectException(ValidationException::class);

        $author = Author::find(1);

        /*
          * We expect exception is thrown because the model with id 1
          * has the code A000001 and it is involved in the unique rule
          */

        $author->code = ['A00001'];

        $author->validate(false);

    }

    /*
    * Test HasValidation validate method without building exclusion rules and checking the
     * validation error bag
    */
    public function testValidateMethodExceptionError()
    {


        $author = Author::find(1);

        /*
          * We expect exception is thrown because the model with id 1
          * has the code A000001 and it is involved in the unique rule
          */

        $author->code = ['A00001'];

        try {
            $author->validate(false);

        } catch (ValidationException $e) {

            $errorsExpected = [
                'code' => [
                    'The code has already been taken.',
                ],
            ];

            $this->assertEquals($e->errors(), $errorsExpected);
        }


    }



}