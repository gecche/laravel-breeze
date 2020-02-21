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

   RELATION TYPES AND AND ARGUMENTS

    HasOne:
        'relationName' => [
            Breeze::$HAS_ONE,
            'related' => required,
            'foreignKey' => optional,
            'localKey' => optional,
        ]

    HasMany:
        'relationName' => [
            Breeze::$HAS_MANY
            'related' => 'required',
            'foreignKey' => optional,
            'localKey' => optional
        ]

    HasManyThrough:
        'relationName' => [
            Breeze::$HAS_MANY_THROUGH
            'related' => 'required',
            'through' => 'required',
            'firstKey' => optional,
            'secondKey' => optional,
            'localKey' => optional,
            'secondLocalKey' => optional
        ]

    BelongsTo:
        'relationName' => [
            Breeze::$BELONGS_TO
            'related' => 'required',
            'foreignKey' => optional,
            'ownerKey' => optional,
            'relation' => optional
        ]

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

    MorphTo:
        'relationName' => [
            Breeze::$MORPH_TO
            'name' => optional,
            'type' => optional,
            'id' => optional
        ]

    MorphOne:
        'relationName' => [
            Breeze::$MORPH_ONE
            'related' => 'required',
            'name' => 'required',
            'type' => optional,
            'id' => optional,
            'localKey' => optional
        ]

    MorphMany:
        'relationName' => [
            Breeze::$MORPH_MANY
            'related' => 'required',
            'name' => 'required',
            'type' => optional,
            'id' => optional,
            'localKey' => optional
        ]

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
     * Can be used to ease declaration of relationships in Ardent models.
     * Follows closely the behavior of the relation methods used by Eloquent, but packing them into an indexed array
     * with relation constants make the code less cluttered.
     *
     * It should be declared with camel-cased keys as the relation name, and value being a mixed array with the
     * relation constant being the first (0) value, the second (1) being the classname and the next ones (optionals)
     * having named keys indicating the other arguments of the original methods: 'foreignKey' (belongsTo, hasOne,
     * belongsToMany and hasMany); 'table' and 'otherKey' (belongsToMany only); 'name', 'type' and 'id' (specific for
     * morphTo, morphOne and morphMany).
     * Exceptionally, the relation type MORPH_TO does not include a classname, following the method declaration of
     * {@link \Illuminate\Database\Eloquent\Model::morphTo}.
     *
     * Example:
     * <code>
     * class Order extends Ardent {
     *     protected static $relations = array(
     *         'items'    => array(self::HAS_MANY, 'Item'),
     *         'owner'    => array(self::HAS_ONE, 'User', 'foreignKey' => 'user_id'),
     *         'pictures' => array(self::MORPH_MANY, 'Picture', 'name' => 'imageable')
     *     );
     * }
     * </code>
     *
     * @see \Illuminate\Database\Eloquent\Model::hasOne
     * @see \Illuminate\Database\Eloquent\Model::hasMany
     * @see \Illuminate\Database\Eloquent\Model::hasManyThrough
     * @see \Illuminate\Database\Eloquent\Model::belongsTo
     * @see \Illuminate\Database\Eloquent\Model::belongsToMany
     * @see \Illuminate\Database\Eloquent\Model::morphTo
     * @see \Illuminate\Database\Eloquent\Model::morphOne
     * @see \Illuminate\Database\Eloquent\Model::morphMany
     * @see \Illuminate\Database\Eloquent\Model::morphToMany
     * @see \Illuminate\Database\Eloquent\Model::morphedByMany
     *
     * @var array
     */
    protected static $relationsData = array();

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


    public function getPivotKeys($relationName)
    {
        $relation = static::$relationsData[$relationName];
        $relationType = $relation[0];

        switch ($relationType) {
            case self::HAS_ONE:
            case self::HAS_MANY:
            case self::BELONGS_TO:
            case self::MORPH_TO:
            case self::MORPH_ONE:
            case self::MORPH_MANY:
                return [];

            case self::BELONGS_TO_MANY:

                if (isset($relation['pivotKeys']) && is_array($relation['pivotKeys'])) {
                    return $relation['pivotKeys'];
                }
                return [];

        }
    }
}
