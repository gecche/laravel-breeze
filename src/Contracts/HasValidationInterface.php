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
interface  HasValidationInterface {




    /*
     * Get the model validation rules, custom messages and custom attributes
     * to be used by an external validator instance
     * Rules, messages and attributes are merged with those passed as arguments.
     */
    /**
     * @param bool $uniqueRules
     * @param null $context
     * @param array $rules
     * @param array $customMessages
     * @param array $customAttributes
     * @return array
     */
    public function getModelValidationSettings($uniqueRules = true, $context = null, $rules = [], $customMessages = [], $customAttributes = []);



    /**
     * Get the array of validation rules, messages and attributes for the class.
     * If $context is null it returns the $rules, $customMessages and $customAttributes properties.
     * Otherwise, return $rulesSets, $customMessagesSets and $customAttributesSets for the given context if
     * it is defined
     *
     *
     * @param null|string $context
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getSelfValidationRules($context = null);



    /**
     * Get a full validator instance for the model
     *
     * @param bool $uniqueRules
     * @param null $context
     * @param array $rules
     * @param array $customMessages
     * @param array $customAttributes
     * @return mixed
     */
    public function getValidator($data = null, $uniqueRules = true, $context = null, $rules = [], $customMessages = [], $customAttributes = []);


    /**
     *
     * Validate the current model instance against the validation rules defined in the class
     * This method is chainable with other Model methods.
     *
     * @param bool $uniqueRules
     * @param null $context
     * @param array $rules
     * @param array $customMessages
     * @param array $customAttributes
     * @return $this
     * @throws ValidationException
     */
    public function validate($uniqueRules = true, $context = null, $rules = [], $customMessages = [], $customAttributes = []);



}
