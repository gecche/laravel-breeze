<?php namespace Gecche\Breeze\Contracts;


/**
 * Breeze - Eloquent model base class with some pluses!
 *
 */
interface  HasRelationshipsInterface {



    /*
     * CONSTANTS FOR RELATIONS TYPES
     */


    const HAS_ONE = 'hasOne';

    const HAS_MANY = 'hasMany';

    const HAS_MANY_THROUGH = 'hasManyThrough';

    const BELONGS_TO = 'belongsTo';

    const BELONGS_TO_MANY = 'belongsToMany';

    const MORPH_TO = 'morphTo';

    const MORPH_ONE = 'morphOne';

    const MORPH_MANY = 'morphMany';

    const MORPH_TO_MANY = 'morphToMany';

    const MORPHED_BY_MANY = 'morphedByMany';

    const HAS_ONE_THROUGH = 'hasOneThrough';

    const BELONGS_TO_THROUGH = 'belongsToThrough';


    /**
     * Returns the array of allowed relation types
     *
     * @return array
     */
    public static function getRelationTypes();

    /**
     * Returns the relational array of the model
     *
     * @return array
     */
    public static function getRelationsData();


}
