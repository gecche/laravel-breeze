<?php

namespace Gecche\Breeze;

use Illuminate\Database\Eloquent\Builder;

class SoftDeletingScope extends \Illuminate\Database\Eloquent\SoftDeletingScope
{

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }

        $builder->onDelete(function (Builder $builder) {
            $column = $this->getDeletedAtColumn($builder);
            $ownershipsColumn = $this->getDeletedByColumn($builder);

            return $builder->updateOwnerships([
                $column => $builder->getModel()->freshTimestampString(),
                $ownershipsColumn => $builder->getModel()->currentUserId(),
            ]);
        });
    }

    /**
     * Get the "deleted at" column for the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return string
     */
    protected function getDeletedByColumn(Builder $builder)
    {
        if (count((array) $builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedDeletedByColumn();
        }

        return $builder->getModel()->getDeletedByColumn();
    }

    /**
     * Add the restore extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addRestore(Builder $builder)
    {
        $builder->macro('restore', function (Builder $builder) {
            $builder->withTrashed();

            return $builder->updateOwnerships([
                $builder->getModel()->getDeletedAtColumn() => null,
                $builder->getModel()->getDeletedByColumn() => null
            ]);
        });
    }


}
