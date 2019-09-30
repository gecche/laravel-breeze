<?php

namespace Gecche\Breeze\Relations\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

trait InteractsWithPivotTableOwnerships
{


    /**
     * Update an existing pivot record on the table.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @param  bool   $touch
     * @return int
     */
    public function updateExistingPivot($id, array $attributes, $touch = true)
    {

        if (in_array($this->updatedBy(), $this->pivotColumns)) {
            $attributes = $this->addOwnershipsToAttachment($attributes, true);
        }

        if (in_array($this->updatedAt(), $this->pivotColumns)) {
            $attributes = $this->addTimestampsToAttachment($attributes, true);
        }

        $updated = $this->newPivotStatementForId($id)->updateOwnerships(
            $this->castAttributes($attributes)
        );

        if ($touch) {
            $this->touchIfTouching();
        }

        return $updated;
    }


    /**
     * Create an array of records to insert into the pivot table.
     *
     * @param  array  $ids
     * @param  array  $attributes
     * @return array
     */
    protected function formatAttachRecords($ids, array $attributes)
    {
        $records = [];

        $hasOwnerships = ($this->hasPivotColumn($this->createdBy()) ||
            $this->hasPivotColumn($this->updatedBy()));

        $hasTimestamps = ($this->hasPivotColumn($this->createdAt()) ||
                  $this->hasPivotColumn($this->updatedAt()));

        // To create the attachment records, we will simply spin through the IDs given
        // and create a new record to insert for each ID. Each ID may actually be a
        // key in the array, with extra attributes to be placed in other columns.
        foreach ($ids as $key => $value) {
            $records[] = $this->formatAttachRecord(
                $key, $value, $attributes, $hasTimestamps, $hasOwnerships
            );
        }

        return $records;
    }

    /**
     * Create a full attachment record payload.
     *
     * @param  int    $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @param  bool   $hasTimestamps
     * @return array
     */
    protected function formatAttachRecord($key, $value, $attributes, $hasTimestamps, $hasOwnerships)
    {
        list($id, $attributes) = $this->extractAttachIdAndAttributes($key, $value, $attributes);

        return array_merge(
            $this->baseAttachRecord($id, $hasTimestamps, $hasOwnerships), $this->castAttributes($attributes)
        );
    }



    /**
     * Create a new pivot attachment record.
     *
     * @param  int   $id
     * @param  bool  $timed
     * @return array
     */
    protected function baseAttachRecord($id, $timed, $owned)
    {
        $record[$this->relatedPivotKey] = $id;

        $record[$this->foreignPivotKey] = $this->parent->{$this->parentKey};

        // If the record needs to have creation and update timestamps, we will make
        // them by calling the parent model's "freshTimestamp" method which will
        // provide us with a fresh timestamp in this model's preferred format.
        if ($timed) {
            $record = $this->addTimestampsToAttachment($record);
        }
        if ($owned) {
            $record = $this->addOwnershipsToAttachment($record);
        }

        return $record;
    }

    /**
     * Set the creation and update timestamps on an attach record.
     *
     * @param  array  $record
     * @param  bool   $exists
     * @return array
     */
    protected function addOwnershipsToAttachment(array $record, $exists = false)
    {
        $userId = $this->parent->currentUserId();

        if (! $exists && $this->hasPivotColumn($this->createdBy())) {
            $record[$this->createdBy()] = $userId;
        }

        if ($this->hasPivotColumn($this->updatedBy())) {
            $record[$this->updatedBy()] = $userId;
        }

        return $record;
    }

}
