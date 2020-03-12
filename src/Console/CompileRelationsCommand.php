<?php

namespace Gecche\Breeze\Console;

use Gecche\Breeze\Contracts\HasRelationshipsInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;
use Illuminate\Console\Command;

/**
 * Class CompileRelationsCommand
 * @package Gecche\Breeze
 *
 * This command compiles the relations of Breeze models defined in their relational array.
 *
 * For each model encountered, it creates a correspondent relational trait in a "relations" subfolder and adds the
 * use of that trait to the Breeze model class.
 *
 * The relational trait contains all the relational methods with the standard Eloquent signature.
 *
 */
class CompileRelationsCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'breeze:relations
                    {model? : Only compile relations for the specified model and not for all the models in the folder}
                    {--dir= : Directory of the models}
                    {--force : Overwrite existing relation traits by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a relation trait for each model by using the relational array.';


    /**
     * @var
     */
    protected $modelsFolder;

    /**
     * @var
     */
    protected $modelsNamespace;
    /**
     * @var array
     */
    protected $models = [];
    /**
     * @var array
     */
    protected $relationErrors = [];


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {

        /*
         * We set the folder, the namespace of the models and the models for compiling relations
         */
        $this->prepareData();

        /*
         * We create the relational traits folder if it does not exist
         */
        $this->createTraitsFolder();

        /*
         * We check for the package's relations stub.
         */
        if (!($traitStub = $this->getStub('RelationsTrait'))) {
            $this->info('RelationTraits stub not found');
            return;
        };


        /*
         * For each model encountered we compile the relations defined in the Breeze relational array
         */
        foreach ($this->models as $modelFilename) {


            /*
             * We try to guess if the current model file is indeed a Breeze model file
             */
            if (($modelData = $this->checkAndGuessModelFile($modelFilename)) === false) {
                $this->info('File ' . $modelFilename . ' not guessed as a model');
                continue;
            }

            $modelName = Arr::get($modelData,'modelName');


            /*
             * Here we build the contents of the relational trait
             */
            $this->relationErrors = [];
            if (!($traitContents = $this->compileTrait($modelData,$traitStub))) {
                $this->info('Empty or not suitable relational array in file ' . $modelFilename);
                continue;
            };



            $traitName = $modelName . 'Relations';
            $traitFileName = $this->modelsFolder . '/Relations/' . $traitName . '.php';

            if (file_exists($traitFileName) && ! $this->option('force')) {
                if (! $this->confirm("The [{$traitName}] trait already exists. Do you want to replace it?")) {
                    continue;
                }
            }

            file_put_contents(
                $traitFileName,
                $traitContents
            );

            $this->writeUseInModel($modelFilename,$modelData,$traitName);


            $this->info('Relation Trait for model '.$modelName.' generated successfully.');
            foreach ($this->relationErrors as $relationName => $relationError) {
                $this->info($relationError);
            }

        }

    }

    /**
     * @param $modelFilename
     * @param $modelData
     * @param $traitName
     */
    protected function writeUseInModel($modelFilename, $modelData, $traitName) {
        $modelContents = Arr::get($modelData,'modelContents');
        if (strstr($modelContents,'use Relations'."\\".$traitName)) {
            return;
        }

        $modelContentsStartingPoint = Arr::get($modelData, 'modelContentsStartingPoint');
        $before = substr($modelContents,0,$modelContentsStartingPoint+1);
        $after = substr($modelContents,$modelContentsStartingPoint+1);
        $modelContents = $before .
            "\n\t".'use Relations'."\\".$traitName.";\n".
            $after;

        file_put_contents(
            $modelFilename,
            $modelContents
        );

    }

    /**
     *
     */
    protected function prepareData()
    {


        $this->modelsFolder = $this->option('dir') ?:
            (Config::get('breeze.default-models-dir') ?:
                app_path());

        $this->modelsNamespace = Config::get('breeze.namespace') ?: "\\App";


        /*
         * Here we get the models files: if the 'model' option is set, we get only that model, otherwise we
         * get all the models in modelsFolder
         */
        $modelName = $this->argument('model');
        $this->models = $modelName ? [$this->getModelFilename($modelName)]
            : glob($this->modelsFolder . '/*.php');


    }


    /**
     * @param $modelName
     * @return string
     */
    protected function getModelFilename($modelName)
    {
        return $this->modelsFolder . '/' . $modelName . '.php';
    }

    /**
     * @param $modelFilename
     * @return array|bool
     */
    protected function checkAndGuessModelFile($modelFilename)
    {


        if (!File::exists($modelFilename)) {
            return false;
        }

        $modelName = File::name($modelFilename);

        $modelContents = File::get($modelFilename);

        $modelClassName = $this->modelsNamespace . '\\' . $modelName;

        try {
            $reflectionObject = new \ReflectionClass($modelClassName);
        } catch (\ReflectionException $e) {
            return false;
        }



        if (!$reflectionObject->implementsInterface(HasRelationshipsInterface::class) ||
            $reflectionObject->getNamespaceName() != $this->modelsNamespace
        ) {
            return false;
        }



        /*
         * We guess the starting position of the class code by looking for the first "{".
         * We will use this point to add the line with the use of the relational trait.
         *
         */
        if (($classContentsStart = strpos($modelContents, '{')) === false
        ) {
            return false;
        }


        /*
         * Finally we get the model relational array
         */

        $modelRelations = $modelClassName::getRelationsData();


        return [
            'modelContents' => $modelContents,
            'modelClassName' => $modelClassName,
            'modelName' => $modelName,
            'modelContentsStartingPoint' => $classContentsStart,
            'modelRelations' => $modelRelations,
        ];


    }

    /**
     * Create the folder for the relational traits files if it does not exists.
     *
     * @return void
     */
    protected function createTraitsFolder()
    {

        if (!is_dir($directory = ($this->modelsFolder . '/Relations'))) {
            mkdir($directory, 0755, true);
        }
    }


    /**
     * @param $modelData
     * @param $traitStub
     * @return bool|mixed
     */
    protected function compileTrait($modelData, $traitStub)
    {
        $traitContents = [];

        $modelRelations = Arr::get($modelData,'modelRelations',[]);
        $modelClassName = Arr::get($modelData,'modelClassName');
        $modelName = Arr::get($modelData,'modelName');
        /*
         * We iterate on the relations to build the trait contents
         */
        foreach ($modelRelations as $name => $relationData) {
            /*
             * We get the relation type checking if it is allowed
             */
            $relationType = Arr::get($relationData, 0);
            if (!in_array($relationType, $modelClassName::getRelationTypes())) {
                $this->relationErrors[$name] =
                    'Relation type not allowed';
                continue;
            }

            /*
             * We compile the trait contents for that relation and we add to the others
             */
            $relationType = ucfirst($relationType);
            $relationContent = $this->compileRelation($relationType,$name, $relationData);

            if (!$relationContent) {
                continue;
            }
            $traitContents[$name] = $relationContent;


        }

        if (count($traitContents) == 0) {
            return false;
        }

        /*
         * We replace the relation trait stub with the suitable data
         */
        $traitStub = str_replace('{{modelsnamespace}}',$this->modelsNamespace,$traitStub);
        $traitStub = str_replace('{{ModelName}}',$modelName,$traitStub);

        $traitRelations = '';
        foreach ($traitContents as $relationName => $relationStub) {
            $traitRelations .= $relationStub . "\n\n";
        }
        $traitStub = str_replace('{{relations}}',$traitRelations,$traitStub);
        return $traitStub;

    }


    /**
     * @param $type
     * @param $name
     * @param $relationData
     * @return bool|false|mixed|string
     */
    protected function compileRelation($type, $name, $relationData)
    {
        /*
         * We get the stub for the relation type provided
         */
        if (!($relationContents = $this->getStub($type, $name, true))) {
            return false;
        };

        /*
         * We get the set of checks we have to perform on the relation data provided
         */
        $dataToCheck = $this->getRelationDataToCheck($type);

        /*
         * We perform the checks and we get the concrete relation data
         */
        $relationData = $this->checkAndGetRelationDataArray($dataToCheck, $relationData, $name);

        /*
         * We fnally compile the relation method stub
         */
        $relationContents = $this->compileRelationStub($relationContents, $name, $relationData);
        return $relationContents;
    }


    /**
     *
     * For each relation type, some arguments are required, some are optional and so on.
     * This methos returns the set of checks to perform on the relation data.
     *
     * @param $type
     * @return array|bool
     */
    protected function getRelationDataToCheck($type)
    {
        switch ($type) {
            case 'HasOne':
                return [
                    'related' => 'required',
                    'foreignKey' => null,
                    'localKey' => null,
                ];


            case 'HasMany':
                return [
                    'related' => 'required',
                    'foreignKey' => null,
                    'localKey' => null
                ];

            case 'HasManyThrough':
                return [
                    'related' => 'required',
                    'through' => 'required',
                    'firstKey' => null,
                    'secondKey' => null,
                    'localKey' => null,
                    'secondLocalKey' => null
                ];

            case 'BelongsTo':
                return [
                    'related' => 'required',
                    'foreignKey' => null,
                    'ownerKey' => null,
                    'relation' => null
                ];

            case 'BelongsToMany':
                return [
                    'related' => 'required',
                    'table' => null,
                    'foreignPivotKey' => null,
                    'relatedPivotKey' => null,
                    'parentKey' => null,
                    'relatedKey' => null,
                    'relation' => null,
                    'pivotFields' => 'nullableArray',
                ];

            case 'MorphTo':
                return [
                    'name' => null,
                    'type' => null,
                    'id' => null
                ];

            case 'MorphOne':
                return [
                    'related' => 'required',
                    'name' => 'required',
                    'type' => null,
                    'id' => null,
                    'localKey' => null
                ];

            case 'MorphMany':
                return [
                    'related' => 'required',
                    'name' => 'required',
                    'type' => null,
                    'id' => null,
                    'localKey' => null
                ];

            case 'MorphToMany':
                return [
                    'related' => 'required',
                    'name' => 'required',
                    'table' => null,
                    'foreignPivotKey' => null,
                    'relatedPivotKey' => null,
                    'parentKey' => null,
                    'relatedKey' => null,
                    'inverse ' => false,
                    'pivotFields' => 'nullableArray',
                ];

            case 'MorphedByMany':
                return [
                    'related' => 'required',
                    'name' => 'required',
                    'table' => null,
                    'foreignPivotKey' => null,
                    'relatedPivotKey' => null,
                    'parentKey' => null,
                    'relatedKey' => null,
                    'pivotFields' => 'nullableArray',
                ];

            default:
                return false;

        }
    }


    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function stubPath()
    {
        return Config::get('breeze.stub-relations-path') ?: __DIR__ . '/stubs';
    }


    /**
     * @param $stubName
     * @param bool $relationName
     * @return bool|false|string
     */
    protected function getStub($stubName, $relationName = false)
    {
        $stubDir = $relationName ? $this->stubPath().'/Relations' : $this->stubPath();
        $stubFileName = $stubDir . '/' . $stubName . '.stub';
        if (file_exists($stubFileName))
            return file_get_contents($stubFileName);

        if ($relationName) {
            $this->relationErrors[$relationName] =
                'Relation ' . $relationName . ' not compiled: stub ' . $stubName . ' not found';

        }

        return false;
    }


    /**
     *
     * Performs check on the provided relation data
     *
     * @param $relationDataFormat
     * @param $relationData
     * @param $relationName
     * @return bool
     */
    protected function checkAndGetRelationDataArray($relationDataFormat, $relationData, $relationName)
    {
        foreach ($relationDataFormat as $key => $requiredOrNullable) {

            if ($requiredOrNullable == 'required' && !($data = Arr::get($relationData, $key))) {
                $this->relationErrors[$relationName] =
                    'Relation ' . $relationName . ' not compiled: missing required parameter ' . $key;
                return false;
            }

            if ($requiredOrNullable == 'nullableArray' && array_key_exists($key,$relationData)) {
                $data = Arr::get($relationData, $key);
                if (!is_array($data)) {
                    $this->relationErrors[$relationName] =
                        'Relation ' . $relationName . ' not compiled: parameter ' . $key . ', if present, must be an array';
                    return false;
                }
            }

            if (is_null($requiredOrNullable) && !array_key_exists($key,$relationData)) {
                $relationData[$key] = null;
            }

            if ($requiredOrNullable === false && !array_key_exists($key,$relationData)) {
                $relationData[$key] = false;
            }

        }

        return $relationData;
    }


    /**
     *
     * compile the relation method stub
     *
     * @param $stub
     * @param $name
     * @param $relationData
     * @return bool|mixed
     */
    protected function compileRelationStub($stub, $name, $relationData)
    {


        $stub =
            str_replace('{{relationName}}', $name, $stub);

        foreach ($relationData as $dataKey => $dataValue) {

            if ($dataKey === 'pivotFields') {
                if (empty($dataValue)) {
                    continue;
                }

                $pivotFields = "['".implode("','",$dataValue)."']";

                $withPivotMethod = ")\n\t\t\t\t\t\t\t->withPivot(".$pivotFields.");";
                $stub = str_replace(');', $withPivotMethod, $stub);
                continue;
            }

            if (is_bool($dataValue)) {
                $stub =
                    str_replace('{{'.$dataKey.'}}', $dataValue ? "true" : "false", $stub);
                continue;
            }
            if (is_null($dataValue)) {
                $stub =
                    str_replace('{{'.$dataKey.'}}', "null", $stub);
                continue;
            }
            if (is_string($dataValue)) {
                $stub =
                    str_replace('{{'.$dataKey.'}}', "'$dataValue'", $stub);
                continue;
            }
            $this->relationErrors[$name] =
                    'Parameter '.$dataKey.' in relation ' . $name . ' is not of a suitable type (string, null, bool)';

            return false;

        }

        return $stub;
    }




}
