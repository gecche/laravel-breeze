<?php

namespace Gecche\Breeze\Relations;

use Gecche\Breeze\Breeze;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Pivot extends \Illuminate\Database\Eloquent\Relations\Pivot
{


    /**
     * Create a new pivot model instance.
     *
     * @param  \Gecche\Breeze\Breeze  $parent
     * @param  array   $attributes
     * @param  string  $table
     * @param  bool    $exists
     * @return static
     */
    public static function fromAttributes(Breeze $parent, $attributes, $table, $exists = false)
    {
        $instance = parent::fromAttributes($parent, $attributes, $table, $exists);

        $instance->ownerships = $instance->hasOwnershipsAttributes();

        return $instance;
    }

    /**
     * Create a new pivot model from raw values returned from a query.
     *
     * @param  \Gecche\Breeze\Breeze  $parent
     * @param  array   $attributes
     * @param  string  $table
     * @param  bool    $exists
     * @return static
     */
    public static function fromRawAttributes(Breeze $parent, $attributes, $table, $exists = false)
    {
        $instance = parent::fromRawAttributes($parent, $attributes, $table, $exists);

        $instance->ownerships = $instance->hasOwnershipsAttributes();

        return $instance;
    }



    /**
     * Determine if the pivot model has ownerships attributes.
     *
     * @return bool
     */
    public function hasOwnershipsAttributes()
    {
        return array_key_exists($this->getCreatedByColumn(), $this->attributes);
    }

    /**
     * Get the name of the "created by" column.
     *
     * @return string
     */
    public function getCreatedByColumn()
    {
        return $this->pivotParent->getCreatedByColumn();
    }

    /**
     * Get the name of the "updated by" column.
     *
     * @return string
     */
    public function getUpdatedByColumn()
    {
        return $this->pivotParent->getUpdatedByColumn();
    }
}
