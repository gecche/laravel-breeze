<?php namespace Gecche\Breeze;

use Gecche\Breeze\Concerns\HasValidation;
use Gecche\Breeze\Concerns\HasFormHelpers;
use Gecche\Breeze\Concerns\HasOwnerships;
use Gecche\Breeze\Concerns\HasRelationships as BreezeHasRelationships;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasRelationships;


/**
 * Breeze - Eloquent model base class with three new useful traits, namely:
 *
 * - HasValidation for validating models
 * - HasOwnerships for keeping track of the user who either creates or (last) updates the model
 * - HasRelationships for defining relations in a cleaner way by using an array
 */

abstract class Breeze extends Model implements BreezeInterface {


    use HasValidation;

    use HasTimestamps;
    use HasOwnerships {
        HasOwnerships::touch insteadof HasTimestamps;
    }

    use HasRelationships;
    use BreezeHasRelationships {
        BreezeHasRelationships::newHasOne insteadof HasRelationships;
        BreezeHasRelationships::newHasMany insteadof HasRelationships;
        BreezeHasRelationships::newBelongsToMany insteadof HasRelationships;
        BreezeHasRelationships::getMorphClass insteadof HasRelationships;
    }

}
