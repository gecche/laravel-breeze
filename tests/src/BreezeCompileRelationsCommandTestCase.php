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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class BreezeCompileRelationsCommandTestCase extends \Orchestra\Testbench\TestCase
{


    protected $modelsNamespace;
    protected $modelsDir;
    protected $relationsDir;
    protected $shouldBeModelsDir;
    protected $shouldBeRelationsDir;
    protected $models = [];

    use RefreshDatabase;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {

        $this->modelsDir = __DIR__ .'/ModelsForCompiling';
        $this->relationsDir = $this->modelsDir . '/Relations';
        $this->shouldBeModelsDir = __DIR__ .'/Models';
        $this->shouldBeRelationsDir = $this->shouldBeModelsDir . '/Relations';
        $this->modelsNamespace = "Gecche\\Breeze\\Tests\\ModelsForCompiling";

        $this->models = [
            'Book',
            'Author',
        ];

        parent::setUp();


        //$this->cleanRelations();
        $this->beforeApplicationDestroyed(function () {
            //$this->cleanRelations();
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


    /*
     * TEST #1 FOR COMPILE RELATIONS COMMAND
     * Compile relations for Gecche\Breeze\Tests\Models\Book and Gecche\Breeze\Tests\Models\Author
     * Check if the Relations folder has been created together with the BookRelationTrait.php file inside it.
     * Int he next tests we check if relations work properly.
     */
    public function testCompileRelationsCommand() {

        $modelNames = [
            //'Book',
            'Author',
        ];


        $this->assertDirectoryNotExists($this->relationsDir);


        foreach ($modelNames as $modelName) {
            $this->artisan('breeze:relations', ['model' => $modelName]);
        }
//       print_r(Artisan::output());
        $this->assertDirectoryExists($this->relationsDir);


        foreach ($modelNames as $modelName) {

            $modelFile = $this->modelsDir .'/'.$modelName.'.php';
            $relationFile = $this->relationsDir .'/'.$modelName.'Relations.php';
            $traitUseLine = "use Relations\\".$modelName."Relations;\n";
            $shouldBeRelationFile = $this->shouldBeRelationsDir .'/'.$modelName.'Relations.php';



            $fileContent = File::get($modelFile);
            $this->assertContains($traitUseLine,$fileContent);

            $this->assertFileExists($relationFile);
            $relationFileContent = File::get($relationFile);
            $shouldBeRelationFileContent = File::get($shouldBeRelationFile);

            $relationFileContent = str_replace('ModelsForCompiling','Models',$relationFileContent);

            $this->assertEquals($relationFileContent,$shouldBeRelationFileContent);

        }

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
//        $this->artisan('breeze:relations');
//
//        $output = Artisan::output();
//
//        $this->assertContains($expectedArtisanOutput,$output);
//        $this->assertDirectoryExists($this->relationsDir);
//        $this->assertFileNotExists($expectedPippoRelationTraitFile);
//    }
//

    protected function removeRelationTraitUse($filename,$modelName) {

        if (!File::exists($filename)) {
            return;
        }

        $fileContent = File::get($filename);

        $relationTraitUseString = "\n\t"."use Relations\\".$modelName."Relations;\n";

        $fileContent = str_replace($relationTraitUseString,'',$fileContent);

        File::put($filename,$fileContent);

    }

    protected function cleanRelations() {

        File::deleteDirectory($this->relationsDir);
        foreach ($this->models as $modelName) {
            $this->removeRelationTraitUse($this->modelsDir.'/'.$modelName.'.php',$modelName);
        }

    }



}