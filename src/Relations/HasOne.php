<?php

namespace Gecche\Breeze\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Concerns\SupportsDefaultModels;

class HasOne extends \Illuminate\Database\Eloquent\Relations\HasOne
{
    /**
     * Perform an update on all the related models.
     *
     * @param  array  $attributes
     * @return int
     */
    public function update(array $attributes)
    {
        if ($this->related->usesOwnerships()) {
            $attributes[$this->relatedUpdatedBy()] = $this->related->currentUserId();
        }

        if ($this->related->usesTimestamps()) {
            $attributes[$this->relatedUpdatedAt()] = $this->related->freshTimestampString();
        }

        return parent::updateOwnerships($attributes);
    }
}
