<?php

namespace Gecche\Breeze\Relations;

use Gecche\Breeze\Relations\Concerns\InteractsWithPivotTableOwnerships;
use Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithPivotTable as InteractsWithPivotTable;

class BelongsToMany extends \Illuminate\Database\Eloquent\Relations\BelongsToMany
{

    use InteractsWithPivotTableOwnerships;

    /**
     * Indicates if ownerships are available on the pivot table.
     *
     * @var bool
     */
    public $withOwnerships = false;

    /**
     * The custom pivot table column for the created_by timestamp.
     *
     * @var string
     */
    protected $pivotCreatedBy;

    /**
     * The custom pivot table column for the updated_by timestamp.
     *
     * @var string
     */
    protected $pivotUpdatedBy;


    /**
     * Touch all of the related models for the relationship.
     *
     * E.g.: Touch all roles associated with this user.
     *
     * @return void
     */
    public function touch()
    {
        $key = $this->getRelated()->getKeyName();

        $columns = [
            $this->related->getUpdatedAtColumn() => $this->related->freshTimestampString(),
            $this->related->getUpdatedByColumn() => $this->related->currentUserId(),
        ];

        // If we actually have IDs for the relation, we will run the query to update all
        // the related model's timestamps, to make sure these all reflect the changes
        // to the parent models. This will help us keep any caching synced up here.
        if (count($ids = $this->allRelatedIds()) > 0) {
            $this->getRelated()->newQuery()->whereIn($key, $ids)->updateOwnerships($columns);
        }
    }



    /**
     * Specify that the pivot table has creation and update ownerships.
     *
     * @param  mixed  $createdBy
     * @param  mixed  $updatedBy
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function withOwnerships($createdBy = null, $updatedBy = null)
    {
        $this->withOwnerships = true;

        $this->pivotCreatedBy = $createdBy;
        $this->pivotUpdatedBy = $updatedBy;

        return $this->withPivot($this->createdBy(), $this->updatedBy());
    }

    /**
     * Get the name of the "created by" column.
     *
     * @return string
     */
    public function createdBy()
    {
        return $this->pivotCreatedBy ?: $this->parent->getCreatedByColumn();
    }

    /**
     * Get the name of the "updated by" column.
     *
     * @return string
     */
    public function updatedBy()
    {
        return $this->pivotUpdatedBy ?: $this->parent->getUpdatedByColumn();
    }

}
