<?php

namespace Gecche\Breeze\Tests\Models\Relations;

trait AuthorRelations
{

    public function books() {

        return $this->hasMany('Gecche\Breeze\Tests\Models\Book', null, null);
    
    }

    public function coauthored() {

        return $this->belongsToMany('Gecche\Breeze\Tests\Models\Book', 'books_coauthors', 'coauthor_id', null,
                                    null, null, null);
    
    }



}