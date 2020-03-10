<?php namespace Gecche\Breeze;

use Gecche\Breeze\Concerns\HasValidation;
use Gecche\Breeze\Concerns\HasFormHelpers;
use Gecche\Breeze\Concerns\HasOwnerships;
use Gecche\Breeze\Concerns\HasRelationships as BreezeHasRelationships;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasRelationships;


/**
 * Breeze - Eloquent model base class with some pluses!
 *
 */
interface BreezeInterface {



    /** This class "has one model" if its ID is an FK in that model */
    const HAS_ONE = 'hasOne';

    /** This class "has many models" if its ID is an FK in those models */
    const HAS_MANY = 'hasMany';

    const HAS_MANY_THROUGH = 'hasManyThrough';

    /** This class "belongs to a model" if it has a FK from that model */
    const BELONGS_TO = 'belongsTo';

    const BELONGS_TO_MANY = 'belongsToMany';

    const MORPH_TO = 'morphTo';

    const MORPH_ONE = 'morphOne';

    const MORPH_MANY = 'morphMany';

    const MORPH_TO_MANY = 'morphToMany';

    const MORPHED_BY_MANY = 'morphedByMany';

    /**
     * The name of the "created by" ownerships column.
     *
     * @var string
     */
    const CREATED_BY = 'created_by';

    /**
     * The name of the "updated by" ownerships column.
     *
     * @var string
     */
    const UPDATED_BY = 'updated_by';



}
