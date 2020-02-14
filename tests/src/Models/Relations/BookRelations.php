<?php

namespace Gecche\Breeze\Tests\Models\Relations;

trait BookRelations
{

    public function author() {

        return $this->belongsTo('Gecche\Breeze\Tests\Models\Author', null, null, null);
    
    }

    public function coauthors() {

        return $this->belongsToMany('Gecche\Breeze\Tests\Models\Author', 'books_coauthors', null, 'coauthor_id',
                                    null, null, null);
    
    }



}
