<?php

namespace Gecche\Breeze\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Class HasValidation
 * @package Gecche\Breeze\Concerns
 *
 *
 * This trait allows to define an array of validation rules (together with optional validation messages and attributes)
 * for an Eloquent model.
 * Rules, messages and attributes are defined as for standard Laravel validation.
 * If you want to define more than one set of rules, e.g one for 'insert' context and one for 'edit' context, you can use
 * the $rulesSets, $customMessagesSets and $customAttributesSets properties in which each first-level key is a context.
 *
 * Moreover, the trait has a "magic" buildUniqueExclusionRules method which applies the current primary key value
 * to the "unique" rules passed to the validator.
 *
 */
trait HasValidation
{
    /**
     * The rules to be applied to the data.
     *
     * @var array
     */
    public static $rules = [];

    /**
     * The array of custom error messages.
     *
     * @var array
     */
    public static $customMessages = [];

    /**
     * The array of custom attributes.
     *
     * @var array
     */
    public static $customAttributes = [];

    /**
     * The sets of rules to be applied to the data depending on context e.g. insert,edit.
     *
     * @var array
     */
    public static $rulesSets = [];

    /**
     * The array of custom error messages depending on context e.g. insert,edit.
     *
     * @var array
     */
    public static $customMessagesSets = [];

    /**
     * The array of custom attributes depending on context e.g. insert,edit.
     *
     * @var array
     */
    public static $customAttributesSets = [];

    /**
     * The validator object in case you need it externally (say, for a form builder).
     *
     * @see getValidator()
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;


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
    public function getModelValidationSettings($uniqueRules = true, $context = null, $rules = [], $customMessages = [], $customAttributes = [])
    {

        list($selfRules, $selfCustomMessages, $selfCustomAttributes) = $this->getSelfValidationRules($context);

        $rules = array_merge($selfRules, $rules);

        if ($this->getKey() && $uniqueRules) {
            $rules = $this->buildUniqueExclusionRules($rules);
        }

        $validationData = [
            'rules' => $rules,
            'customMessages' => array_merge($selfCustomMessages,$customMessages),
            'customAttributes' => array_merge($selfCustomAttributes,$customAttributes),
        ];

        return $validationData;

    }


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
    public function getSelfValidationRules($context = null)
    {

        if (is_null($context)) {
            return [
                static::$rules,
                static::$customMessages,
                static::$customAttributes,
            ];
        }

        if (!array_key_exists($context, static::$rulesSets)) {
            throw new \InvalidArgumentException("Validation rules for context " . $context . " have not been defined.");
        }
        return [
            static::$rulesSets[$context],
            Arr::get(static::$customMessagesSets, $context, []),
            Arr::get(static::$customAttributesSets, $context, []),
        ];


    }


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
    public function getValidator($data = null, $uniqueRules = true, $context = null, $rules = [], $customMessages = [], $customAttributes = [])
    {
        $validatorSettings = $this->getModelValidationSettings($uniqueRules, $context, $rules, $customMessages, $customAttributes);

        $data = $data ?: $this->getAttributes();


        return Validator::make($data, $validatorSettings['rules'], $validatorSettings['customMessages'], $validatorSettings['customAttributes']);
    }


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
    public function validate($uniqueRules = true, $context = null, $rules = [], $customMessages = [], $customAttributes = [])
    {
        $validatorSettings = $this->getModelValidationSettings($uniqueRules, $context, $rules, $customMessages, $customAttributes);

        $validator = Validator::make($this->getAttributes(), $validatorSettings['rules'], $validatorSettings['customMessages'], $validatorSettings['customAttributes']);

        if ($validator->passes()) {
            return $this;
        }

        throw new ValidationException($validator);

    }

    /**
     * Appends the model ID to the 'unique' rules given. The resulting array can
     * then be fed to a Ardent save so that unchanged values don't flag a validation
     * issue. It can also be used with {@link Illuminate\Foundation\Http\FormRequest}
     * to painlessly validate model requests.
     * Rules can be in either strings with pipes or arrays, but the returned rules
     * are in arrays.
     *
     * @param array $rules
     * @return array Rules with exclusions applied
     */
    public function buildUniqueExclusionRules(array $rules = [])
    {

        if (!count($rules))
            $rules = static::$rules;

        foreach ($rules as $field => &$ruleset) {
            // If $ruleset is a pipe-separated string, switch it to array
            $ruleset = (is_string($ruleset)) ? explode('|', $ruleset) : $ruleset;

            foreach ($ruleset as &$rule) {
                if (strpos($rule, 'unique:') === 0) {
                    // Stop splitting at 4 so final param will hold optional where clause
                    $params = explode(',', $rule, 4);

                    $uniqueRules = [];

                    // Append table name if needed
                    $table = explode(':', $params[0]);
                    if (count($table) == 1) {
                        $uniqueRules[1] = $this->getTable();
                    } else {
                        $uniqueRules[1] = $table[1];
                    }

                    // Append field name if needed
                    if (count($params) == 1) {
                        $uniqueRules[2] = $field;
                    } else {
                        $uniqueRules[2] = $params[1];
                    }

                    if (isset($this->primaryKey)) {
                        if (isset($this->{$this->primaryKey})) {
                            $uniqueRules[3] = $this->{$this->primaryKey};

                            // If optional where rules are passed, append them otherwise use primary key
                            $uniqueRules[4] = isset($params[3]) ? $params[3] : $this->primaryKey;
                        }
                    } else {
                        if (isset($this->id)) {
                            $uniqueRules[3] = $this->id;
                        }
                    }

                    $rule = 'unique:' . implode(',', $uniqueRules);
                }
            }

            $ruleset = implode('|',$ruleset);
        }

        return $rules;
    }


}
