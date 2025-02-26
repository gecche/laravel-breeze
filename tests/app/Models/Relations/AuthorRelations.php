<?php

namespace Gecche\Breeze\Tests\App\Models\Relations;

trait AuthorRelations
{

    public function books() {

        return $this->hasMany('Gecche\Breeze\Tests\App\Models\Book', null, null);
    
    }

    public function coauthored() {

        return $this->belongsToMany('Gecche\Breeze\Tests\App\Models\Book', 'books_coauthors', 'coauthor_id', null,
                                    null, null, null)
							->withPivot(['created_at','updated_at','created_by','updated_by','percentage']);
    
    }



}
