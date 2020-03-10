<?php

namespace Gecche\Breeze\Relations;


abstract class Relation extends \Illuminate\Database\Eloquent\Relations\Relation
{


    /**
     * Touch all of the related models for the relationship.
     *
     * @return void
     */
    public function touch()
    {
        $column = $this->getRelated()->getUpdatedAtColumn();
        $ownershipsColumn = $this->getRelated()->getUpdatedByColumn();

        $this->rawUpdate([
            $column => $this->getRelated()->freshTimestampString(),
            $ownershipsColumn => $this->getRelated()->currentUserId(),
        ]);

    }

    /**
     * Run a raw update against the base query.
     *
     * @param  array  $attributes
     * @return int
     */
    public function rawUpdate(array $attributes = [])
    {
        return $this->query->withoutGlobalScopes()->updateOwnerships($attributes);
    }

    /**
     * Get the name of the "created by" column.
     *
     * @return string
     */
    public function createdBy()
    {
        return $this->parent->getCreatedByColumn();
    }

    /**
     * Get the name of the "updated by" column.
     *
     * @return string
     */
    public function updatedBy()
    {
        return $this->parent->getUpdatedByColumn();
    }

    /**
     * Get the name of the related model's "updated by" column.
     *
     * @return string
     */
    public function relatedUpdatedBy()
    {
        return $this->related->getUpdatedByColumn();
    }


}
