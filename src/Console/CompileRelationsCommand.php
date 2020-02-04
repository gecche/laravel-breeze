<?php

namespace Gecche\Breeze\Console;

use Gecche\Breeze\Breeze;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;

class CompileRelationsCommand extends Command
{

    use DetectsApplicationNamespace;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'breeze:relations
                    {model? : Only compile relations for the specified model}
                    {--dir= : Directory of the models}
                    {--force : Overwrite existing relation traits by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a relation trait for each model by using the relational array.';


    protected $dir;
    protected $fullDir;
    protected $namespace;
    protected $models = [];
    protected $relationErrors = [];


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {

        $this->getFilesData();

        $this->createDirectories();

        if (!($traitStub = $this->getStub('RelationsTrait'))) {
            $this->info('RelationTraits stub not found');
            return;
        };


        foreach ($this->models as $modelFilename) {
            if (($modelData = $this->checkAndGuessModelFile($modelFilename)) === false) {
                $this->info('File ' . $modelFilename . ' not guessed as a model');
                continue;
            }


            if (!($modelRelations = $this->getModelRelations($modelData))) {
                $this->info('Empty or not suitable relational array in file ' . $modelFilename);
                continue;
            };

            $modelRelativeClassName = array_get($modelData,'modelRelativeClassName');

            $this->relationErrors = [];
            if (!($traitContents = $this->compileTrait($modelRelations,$modelRelativeClassName,$traitStub))) {
                $this->info('Empty or not suitable relational array in file ' . $modelFilename);
                continue;
            };

            $traitName = $modelRelativeClassName . 'Relations';
            $traitFileName = $this->fullDir . '/Relations/' . $traitName . '.php';

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


            $this->info('Relation Trait for model '.$modelRelativeClassName.' generated successfully.');
            foreach ($this->relationErrors as $relationName => $relationError) {
                $this->info($relationError);
            }

        }

    }

    protected function writeUseInModel($modelFilename,$modelData,$traitName) {
        $modelContents = array_get($modelData,'modelContents');
        if (strstr($modelContents,'use Relations'."\\".$traitName)) {
            return;
        }

        $modelContentsStartingPoint = array_get($modelData, 'modelContentsStartingPoint');
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

    protected function getFilesData()
    {

        $this->dir = $this->option('dir') ?:
            (config('breeze.default-models-dir') ?:
                base_path('app'));

        $this->fullDir = $this->dir;

        $this->namespace = config('breeze.namespace') ?: $this->getAppNamespace();

        $modelName = $this->argument('model');

        $this->models = $modelName ? [$this->getModelFilename($modelName)]
            : glob($this->fullDir . '/*.php');


    }


    protected function getModelFilename($modelName)
    {
        return $this->fullDir . '/' . $modelName . '.php';
    }

    protected function checkAndGuessModelFile($modelFilename)
    {


        if (!file_exists($modelFilename)) {
            return false;
        }

        $modelRelativeName = $this->guessModelNameFromFilename($modelFilename);
        $modelContents = file_get_contents($modelFilename);

        if (!str_contains($modelContents, 'namespace ' . $this->namespace)
            || !str_contains($modelContents, 'class ' . $modelRelativeName . ' extends Breeze')
            || ($classContentsStart = strpos($modelContents, '{')) === false
        ) {
            return false;
        }

        return [
            'modelContents' => $modelContents,
            'modelRelativeClassName' => $modelRelativeName,
            'modelClassName' => $this->namespace . '\\' . $modelRelativeName,
            'modelContentsStartingPoint' => $classContentsStart,
        ];


    }

    protected function guessModelNameFromFilename($filename)
    {

        $rightPart = substr($filename, strrpos($filename, '/') + 1);
        return substr($rightPart, 0, -4);
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {

        if (!is_dir($directory = ($this->fullDir . '/Relations'))) {
            mkdir($directory, 0755, true);
        }
    }


    protected function getModelRelations($modelData)
    {
        $modelClassName = array_get($modelData, 'modelClassName');

        $relationsData = $modelClassName::getRelationsData();

        return is_array($relationsData) ? $relationsData : false;

    }

    protected function compileTrait($modelRelations,$modelClassName,$traitStub)
    {
        $traitContents = [];

        foreach ($modelRelations as $name => $relationData) {
            $relationType = array_get($relationData, 0);
            if (!in_array($relationType, Breeze::getRelationTypes())) {
                $this->relationErrors[$name] =
                    'Relation type not allowed';
                continue;
            }

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

        $traitStub = str_replace('{{modelsnamespace}}',$this->namespace,$traitStub);
        $traitStub = str_replace('{{ModelName}}',$modelClassName,$traitStub);

        $traitRelations = '';
        foreach ($traitContents as $relationName => $relationStub) {
            $traitRelations .= $relationStub . "\n\n";
        }
        $traitStub = str_replace('{{relations}}',$traitRelations,$traitStub);
        return $traitStub;

    }


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
                    'relation' => null
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
                    'inverse ' => false
                ];

            case 'MorphedByMany':
                return [
                    'related' => 'required',
                    'name' => 'required',
                    'table' => null,
                    'foreignPivotKey' => null,
                    'relatedPivotKey' => null,
                    'parentKey' => null,
                    'relatedKey' => null
                ];

            default:
                return false;

        }
    }

    protected function compileRelation($type, $name, $relationData)
    {
        if (!($relationContents = $this->getStub($type, $name, true))) {
            return false;
        };

        $dataToCheck = $this->getRelationDataToCheck($type);
        $relationData = $this->checkAndGetRelationDataArray($dataToCheck, $relationData, $name);
        $relationContents = $this->compileRelationStub($relationContents, $name, $relationData);
        return $relationContents;
    }


    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function stubPath()
    {
        return config('breeze.stub-relations-path') ?: __DIR__ . '/stubs';
    }


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


    protected function checkAndGetRelationDataArray($relationDataFormat, $relationData, $relationName)
    {
        foreach ($relationDataFormat as $key => $requiredOrNullable) {

            if ($requiredOrNullable == 'required' && !($data = array_get($relationData, $key))) {
                $this->relationErrors[$relationName] =
                    'Relation ' . $relationName . ' not compiled: missing required parameter ' . $key;
                return false;
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


    protected function compileRelationStub($stub, $name, $relationData)
    {


        $stub =
            str_replace('{{relationName}}', $name, $stub);

        foreach ($relationData as $dataKey => $dataValue) {
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
