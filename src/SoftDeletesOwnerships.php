<?php

namespace Gecche\Breeze;

use Illuminate\Database\Eloquent\SoftDeletes;

trait SoftDeletesOwnerships
{


    use SoftDeletes;
    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootSoftDeletes()
    {
        static::addGlobalScope(new SoftDeletingScope);
    }


    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runSoftDelete()
    {
        $query = $this->newModelQuery()->where($this->getKeyName(), $this->getKey());

        $time = $this->freshTimestamp();
        $userId = $this->currentUserId();

        $columns = [
            $this->getDeletedAtColumn() => $this->fromDateTime($time),
            $this->getDeletedByColumn() => $userId,
        ];

        $this->{$this->getDeletedAtColumn()} = $time;
        $this->{$this->getDeletedByColumn()} = $userId;

        if ($this->timestamps && ! is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;

            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }
        if ($this->usesOwnerships() && ! is_null($this->getUpdatedByColumn())) {
            $this->{$this->getUpdatedByColumn()} = $time;

            $columns[$this->getUpdatedByColumn()] = $userId;
        }

        $query->updateOwnerships($columns);
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool|null
     */
    public function restore()
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getDeletedAtColumn()} = null;
        $this->{$this->getDeletedByColumn()} = null;

        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }


    /**
     * Get the name of the "deleted by" column.
     *
     * @return string
     */
    public function getDeletedByColumn()
    {
        return defined('static::DELETED_BY') ? static::DELETED_BY : 'deleted_by';
    }

    /**
     * Get the fully qualified "deleted by" column.
     *
     * @return string
     */
    public function getQualifiedDeletedByColumn()
    {
        return $this->qualifyColumn($this->getDeletedByColumn());
    }
}
