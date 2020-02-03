<?php

namespace Gecche\Breeze\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasOwnerships
{
    /**
     * Indicates if the model should be ownershipped.
     *
     * @var bool
     */
    public $ownerships = false;

    /**
     * Update the model's update timestamp.
     *
     * @return bool
     */
    public function touch()
    {
        if (! $this->usesTimestamps() && ! $this->usesOwnerships()) {
            return false;
        }

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        if ($this->usesOwnerships()) {
            $this->updateOwnerships();
        }

        return $this->save();
    }

    /**
     * Update the creation and update timestamps.
     *
     * @return void
     */
    protected function updateOwnerships()
    {

        $userId = Auth::id();

        if (! is_null(static::UPDATED_BY) && ! $this->isDirty(static::UPDATED_BY)) {
            $this->setUpdatedBy($userId);
        }

        if (! $this->exists && ! $this->isDirty(static::CREATED_BY)) {
            $this->setCreatedBy($userId);
        }
    }

    /**
     * Set the value of the "created by" attribute.
     *
     * @param  mixed $value
     * @return void
     */
    public function setCreatedBy($value)
    {
        $this->{static::CREATED_BY} = $value;
    }

    /**
     * Set the value of the "updated by" attribute.
     *
     * @param  mixed $value
     * @return void
     */
    public function setUpdatedBy($value)
    {
        $this->{static::UPDATED_BY} = $value;
    }


    /**
     * Determine if the model uses timestamps.
     *
     * @return bool
     */
    public function usesOwnerships()
    {
        return $this->ownerships;
    }

    /**
     * Get the name of the "created by" column.
     *
     * @return string
     */
    public function getCreatedByColumn()
    {
        return static::CREATED_BY;
    }

    /**
     * Get the name of the "updated by" column.
     *
     * @return string
     */
    public function getUpdatedByColumn()
    {
        return static::UPDATED_BY;
    }

    /**
     * Get a current user id for the model.
     *
     * @return int
     */
    public function currentUserId()
    {
        return Auth::id();
    }

    /**
     * Perform a model update operation (with ownerships).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performUpdate(Builder $query)
    {
        // If the updating event returns false, we will cancel the update operation so
        // developers can hook Validation systems into their models and cancel this
        // operation if the model does not pass validation. Otherwise, we update.
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        // First we need to create a fresh query instance and touch the creation and
        // update timestamp on the model which are maintained by us for developer
        // convenience. Then we will just continue saving the model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }
        if ($this->usesOwnerships()) {
            $this->updateOwnerships();
        }

        // Once we have run the update operation, we will fire the "updated" event for
        // this model instance. This will allow developers to hook into these after
        // models are updated, giving them a chance to do any special processing.
        $dirty = $this->getDirty();

        if (count($dirty) > 0) {

            $this->setKeysForSaveQuery($query)->updateOwnerships($dirty);

            $this->fireModelEvent('updated', false);

            $this->syncChanges();
        }

        return true;
    }

    /**
     * Perform a model insert operation (with ownerships).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performInsert(Builder $query)
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        // First we'll need to create a fresh query instance and touch the creation and
        // update timestamps on this model, which are maintained by us for developer
        // convenience. After, we will just continue saving these model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }
        if ($this->usesOwnerships()) {
            $this->updateOwnerships();
        }

        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.
        $attributes = $this->attributes;

        if ($this->getIncrementing()) {
            $this->insertAndSetId($query, $attributes);
        }

        // If the table isn't incrementing we'll simply insert these attributes as they
        // are. These attribute arrays must contain an "id" column previously placed
        // there by the developer as the manually determined key for these models.
        else {
            if (empty($attributes)) {
                return true;
            }

            $query->insert($attributes);
        }

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = true;

        $this->wasRecentlyCreated = true;

        $this->fireModelEvent('created', false);

        return true;
    }



}
