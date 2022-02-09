# laravel-breeze
Collection of traits for improving eloquent model's features

[![Laravel](https://img.shields.io/badge/Laravel-5.x-orange.svg?style=flat-square)](http://laravel.com)
[![Laravel](https://img.shields.io/badge/Laravel-6.x-orange.svg?style=flat-square)](http://laravel.com)
[![License](http://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://tldrlegal.com/license/mit-license)

## Description
Clean and static relations definition, validation with handling of `unique` rule and model's ownerships.  This package allows a single Laravel installation to work with multiple HTTP domains.
Breeze: a powerful trait-based Eloquent extension. 

- Validation trait. Allowing definition of specific validation rules sets directly in the model with the optional automatic 
instantiation of the `unique` validation rule for skipping the model itself when validating. 
Multiple validation rules sets could be defined, e.g. "insert" rules and "edit" rules.

- Relations trait. The definition of the model's relations can be done via a simple and clean array allowing for static 
 inspection of relations at run-time. The package provides an artisan command for automatically compiling such 
 relational array in standard Eloquent relational methods, putting them in a clean and well-organized trait.
 
- Ownerships trait. The package allows for using `ownerships` fields for tracking the user responsible for the creation 
and the last update of a model. Ownerships fields work exactly as `timestamps` fields and the package also 
handles migrations in the same way. 

Each trait can be used separately in a standard Eloquent model, but a convenient Breeze model extension is provided. 
The package is fully tested.

## Documentation

### Version Compatibility

 Laravel  | Breeze
:---------|:----------
 5.5.x    | 1.1.x
 5.6.x    | 1.2.x
 5.7.x    | 1.3.x
 5.8.x    | 1.4.x
 6.x      | 2.x
 7.x      | 3.x
 8.x      | 4.x
 9.x      | 5.x

### Installation

Add gecche/laravel-breeze as a requirement to composer.json:

```javascript
{
    "require": {
        "gecche/laravel-breeze": "5.*"
    }
}
```

Update your packages with composer update or install with composer install.

You can also add the package using `composer require gecche/laravel-breeze` and later 
specify the version you want (for now, dev-v1.1.* is your best bet).

This package makes use of the discovery feature.

### Breeze Usage

Given a standard Eloquent model, e.g an `Author` model:

```
<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
```

To use Breeze features, simply change to: 

```
<?php namespace App;

#use Illuminate\Database\Eloquent\Model;
use Gecche\Breeze\Breeze;

class Author extends Breeze
{
```

That's it. Each of the package's traits are included in the Breeze model extension.

### Validation usage

Add validation rules to the Breeze class defining them in the static property `$rules` using the standard 
Laravel's validation syntax:

```
class Author extends Breeze
{

    public static $rules = [
       'surname' => 'required',
       'code' => 'required|unique:authors,code'
    ];
```

To validate a model simply do:


```
$author = Author::find(1); //instantiate a Breeze model

$author->validate();
```

The Breeze's `validate` method is chainable and it throws a `ValidationException` in case of failure.

```
$author->validate()->save();
```

The `validate` method automatically instantiates each `unique` validation rules for skipping the model itself when 
executing those rules. In the above example, when the method is called, the validation rules would become

```
    [
       'surname' => 'required',
       'code' => 'required|unique:authors,code,id,1' //id is the key of an Author.
    ]
```

To prevent this behaviour simply call
 
```
$author->validate(false);
```
 
Typically, a model class has a single set of validation rules. However, it could be the case that a certain model 
has different validation rules depending upon the "context", e.g. some rules apply only when "updating" a model and not 
when "inserting" a new model.
 
To address these cases, the pacckage allows for defining rules in an alternative way, by using the `$rulesSets` static
property.

```
class Author extends Breeze
{

   public static $rulesSets = [
        'insert' => [
           'surname' => 'required',
           'code' => 'required|unique:authors,code'
        ],
        'edit' => [
           'surname' => 'required',
           'code' => 'required|unique:authors,code',
           'birthdate' => 'required',
        ],
   ];
```

The associative keys of the array should be used as the second argument in the `validate` method:
 
```
$author = Author::find(1); //instantiate a Breeze model

$author->validate(true,"edit");
```

The package also offers another method to simply get the validator instance without running validation.


 ```
 $author = Author::find(1); //instantiate a Breeze model
 
 $author->getValidator();
 ```
 
The `getValidator` method returns a standard Laravel's validator object instantiated with the current model's data. 
If you want to instantiate the validator with another data, simply pass them as the first argument.

```
 $author = Author::find(1); //instantiate a Breeze model
 $otherData = array_merge($author->toArray(),['otherStuff' => 'otherStuffValue]);
 
 $author->getValidator($otherData);
 ```

As for the `validate` method, you can specify if you don't want the instantiation of the `unique` validation rules and 
the specific set of validation rules to apply. They are the second and third argument respectively.

The instantiation of the `unique` validation rules is directly borrowed from 
the [Laravel Ardent](https://github.com/laravel-ardent/ardent) package.

### Relationships usage

The Breeze package allows for defining relationships in a cleaner way, with the use of a single associative array 
defined in the static `$relationsData` property.

```
class Author extends Breeze
{

    public static $relationsData = [
        'books' => [
            Breeze::HAS_MANY, 
            'related' => Book::class
        ],

    ];
```

In the above example, we define a standard Laravel's `HAS_MANY` relation named `books`. 
Each key in the relational array represents the name of the relation. For each entry the first of its values represents 
the relation type (`HAS_MANY`, `BELONGS_TO`, `BELONGS_TO_MANY` ...)  while the other values 
are the same values required in the standard Laravel's relational methods. For example, given the 
definition of the Laravel's `HAS_MANY` relation type method:

```
public function hasMany($related, $foreignKey = null, $localKey = null)
```

the correspondent `HAS_MANY` Breeze's relational array entry expects the following values:

```
'relationName' => [
    Breeze::$HAS_MANY //A convenient constant is defined for each relation type
    'related' => 'required',
    'foreignKey' => optional,
    'localKey' => optional
]
```

The full list of arguments for each relation type is defined in the `HasRelationships` trait.

#### The `breeze:relations` artisan command

Once defined the relational array, the model's relations should be taken into account when querying the model as usual.

Until now, the relationships definition is similar to the one found in the
 [Laravel Ardent](https://github.com/laravel-ardent/ardent) package, but the Breeze package handles such 
 relationships definition in a new totally different way.
 
While the Ardent package compiles the relations at run-time, we instead make use of a new artisan command in order to: 

- Compile the relational array into standard Laravel's relational methods, by using exactly the same arguments
- Organize all the relations in a separate trait
- Adds the trait to the main model.

In the above example, to compile the Author's relations, we simply run
 
 ```
  php artisan breeze:relations
 ```

which creates a new `AuthorRelations` trait placed in a `Relations` subfolder of the models' folder 

```
<?php namespace App\Relations;

trait AuthorRelations
{

    public function books() {

        return $this->hasMany('App\Book', null, null);
    
    }

}
```

and updates the Author model for the use of the generated trait

```
class Author extends Breeze
{

    use Relations\AuthorRelations;

    public static $relationsData = [
        'books' => [
            Breeze::HAS_MANY, 
            'related' => Book::class
        ],

    ];
```

The `breeze:relations` command scans for the whole model's folder, accordingly to the models namespace, and it compiles 
the relations for each guessed model file. To compile only a certain model, an optional argument can be provided:

 ```
  php artisan breeze:relations Author
 ```

If a relation trait is already present in the subfolder, relations are not compiled unless the `--force` option is used.

### Ownerships usage

The ownerships trait allows for a Breeze model to use "ownerships fields" in the same way as standard 
timestamps fields in Eloquent models.

The Breeze model has a new public `ownerships` property which can be turned on:
   
```
class Author extends Breeze
{

    protected $table = 'authors';

    public $ownerships = true;

...
```

By default the Breeze model expects that the associated table contains two ownerships fields, namely 
 `created_by` and `updated_by` keeping track of the currently authenticated user which has created the model 
 and the last one which has updated the model. 
 
The packages handles the automatic update of the ownerships fields in the same way as timestamps 
are managed by stanrd Eloquent models.

#### Migrations for ownerships

The Breeze package adds the `ownerships` and `nullableOwnerships` macro for the
`Schema` builder which add to a table the `created_by` and `updated_by` 
fields with an integer type either not nullable or nullable respectively.
The Breeze package also overrides the standard `migrate:make` command by adding the  
`ownerships` option which can take `yes`, `null` and `no` as values (default: `no`).
 
For example:

```
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();

            ...
            
            $table->timestamps();
            
            $table->ownerships();
        });
    }
    
    ...


};
```



