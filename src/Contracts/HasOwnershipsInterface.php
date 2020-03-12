<?php namespace Gecche\Breeze\Contracts;

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
interface  HasOwnershipsInterface {



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


    /**
     * Set the value of the "created by" attribute.
     *
     * @param  mixed $value
     * @return void
     */
    public function setCreatedBy($value);

    /**
     * Set the value of the "updated by" attribute.
     *
     * @param  mixed $value
     * @return void
     */
    public function setUpdatedBy($value);


    /**
     * Determine if the model uses timestamps.
     *
     * @return bool
     */
    public function usesOwnerships();

    /**
     * Get the name of the "created by" column.
     *
     * @return string
     */
    public function getCreatedByColumn();

    /**
     * Get the name of the "updated by" column.
     *
     * @return string
     */
    public function getUpdatedByColumn();

    /**
     * Get a current user id for the model.
     *
     * @return int
     */
    public function currentUserId();


}
