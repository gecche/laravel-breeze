<?php

namespace Gecche\Breeze\Relations;


class HasMany extends \Illuminate\Database\Eloquent\Relations\HasMany
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
