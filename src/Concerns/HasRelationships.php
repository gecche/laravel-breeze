<?php

namespace Gecche\Breeze\Concerns;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

use Gecche\Breeze\Relations\HasOne;
use Gecche\Breeze\Relations\HasMany;

use Gecche\Breeze\Relations\Relation;

use Gecche\Breeze\Relations\BelongsToMany;


/*
 *
 *
 *




 */

trait HasRelationships
{

    /**
     * Array of relations used to verify arguments used in the {@link $relationsData}
     *
     * @var array
     */
    protected static $relationTypes = array(
        self::HAS_ONE, self::HAS_MANY, self::HAS_MANY_THROUGH,
        self::BELONGS_TO, self::BELONGS_TO_MANY,
        self::MORPH_TO, self::MORPH_ONE, self::MORPH_MANY,
        self::MORPH_TO_MANY, self::MORPHED_BY_MANY
    );


    /**
     * This array is used to define relations in a static way and then compiled via
     * the Gecche\Breeze\Console\CompileRelationsCommand.
     *
     * Each entry of the array has the relation name as its key and another array for the relations' parameters
     * as its value.
     * E.g.
     * class Author extends Breeze {
     *
     * ...
     *
     * protected static $relationsData = [
     *       'books' => [
     *          self::HAS_MANY,
     *          'related' => Book::class
     *      ],
     *
     *       'coauthored' => [
     *          self::BELONGS_TO_MANY,
     *           'related' => Book::class,
     *           'table' => 'books_coauthors',
     *           'foreignPivotKey' => 'coauthor_id',
     *           'pivotFields' => ['created_at','updated_at','percentage'],
     *     ],
     * ];
     *
     * ...
     *
     * }
     *
     * In the relation's parameters array, the first entry should be the relation type using a constant defined
     * Gecche\Breeze\Contracts\HasRelationshipsInterface.
     * The other entries are exactly the same values defined in the Eloquent relation methods and they are listed below.
     * The only addition is the 'pivotFields' entry which can be used in any relation type which handles pivot fields.
     *
     *
     * @see \Illuminate\Database\Eloquent\Model::hasOne
        HasOne:
        'relationName' => [
            Breeze::$HAS_ONE,
            'related' => required,
            'foreignKey' => optional,
            'localKey' => optional,
        ]

     * @see \Illuminate\Database\Eloquent\Model::hasMany
        HasMany:
        'relationName' => [
            Breeze::$HAS_MANY,
            'related' => 'required',
            'foreignKey' => optional,
            'localKey' => optional
        ]

     * @see \Illuminate\Database\Eloquent\Model::hasManyThrough
        HasManyThrough:
        'relationName' => [
            Breeze::$HAS_MANY_THROUGH,
            'related' => 'required',
            'through' => 'required',
            'firstKey' => optional,
            'secondKey' => optional,
            'localKey' => optional,
            'secondLocalKey' => optional
        ]

     * @see \Illuminate\Database\Eloquent\Model::belongsTo
        BelongsTo:
            'relationName' => [
            Breeze::$BELONGS_TO
            'related' => 'required',
            'foreignKey' => optional,
            'ownerKey' => optional,
            'relation' => optional
        ]

     * @see \Illuminate\Database\Eloquent\Model::belongsToMany
        BelongsToMany:
            'relationName' => [
            Breeze::$BELONGS_TO_MANY
            'related' => 'required',
            'table' => optional,
            'foreignPivotKey' => optional,
            'relatedPivotKey' => optional,
            'parentKey' => optional,
            'relatedKey' => optional,
            'relation' => optional,
            'pivotFields' => 'nullableArray'
        ]

     * @see \Illuminate\Database\Eloquent\Model::morphTo
        MorphTo:
            'relationName' => [
            Breeze::$MORPH_TO
            'name' => optional,
            'type' => optional,
            'id' => optional
        ]

     * @see \Illuminate\Database\Eloquent\Model::morphOne
        MorphOne:
            'relationName' => [
            Breeze::$MORPH_ONE
            'related' => 'required',
            'name' => 'required',
            'type' => optional,
            'id' => optional,
            'localKey' => optional
        ]

     * @see \Illuminate\Database\Eloquent\Model::morphMany
        MorphMany:
            'relationName' => [
            Breeze::$MORPH_MANY
            'related' => 'required',
            'name' => 'required',
            'type' => optional,
            'id' => optional,
            'localKey' => optional
        ]

     * @see \Illuminate\Database\Eloquent\Model::morphToMany
        MorphToMany:
            'relationName' => [
            Breeze::$MORPH_TO_MANY
            'related' => 'required',
            'name' => 'required',
            'table' => optional,
            'foreignPivotKey' => optional,
            'relatedPivotKey' => optional,
            'parentKey' => optional,
            'relatedKey' => optional,
            'inverse ' => false,
            'pivotFields' => 'nullableArray'
        ]

     * @see \Illuminate\Database\Eloquent\Model::morphedByMany
        MorphedByMany:
            'relationName' => [
            Breeze::$MORPHED_BY_MANY
            'related' => 'required',
            'name' => 'required',
            'table' => optional,
            'foreignPivotKey' => optional,
            'relatedPivotKey' => optional,
            'parentKey' => optional,
            'relatedKey' => optional,
            'pivotFields' => 'nullableArray'
        ]
     *
     *
     * @var array
     */
    protected static $relationsData = [];

    /**
     * @return array
     */
    public static function getRelationTypes()
    {
        return self::$relationTypes;
    }

    /**
     * @return array
     */
    public static function getRelationsData()
    {
        return static::$relationsData;
    }

    /**
     * Instantiate a new HasOne relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    protected function newHasOne(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return new HasOne($query, $parent, $foreignKey, $localKey);
    }


    /**
     * Instantiate a new HasMany relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    protected function newHasMany(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return new HasMany($query, $parent, $foreignKey, $localKey);
    }


    /**
     * Instantiate a new BelongsToMany relationship.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param string $parentKey
     * @param string $relatedKey
     * @param string $relationName
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function newBelongsToMany(Builder $query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey,
                                        $parentKey, $relatedKey, $relationName = null)
    {
        return new BelongsToMany($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }


    /**
     * Get the class name for polymorphic relations.
     *
     * @return string
     */
    public function getMorphClass()
    {
        $morphMap = Relation::morphMap();

        if (!empty($morphMap) && in_array(static::class, $morphMap)) {
            return array_search(static::class, $morphMap, true);
        }

        return static::class;
    }


}
