<?php

namespace Gecche\Breeze\Concerns;

use Illuminate\Support\Facades\Validator;

trait HasValidation
{
    /**
     * The rules to be applied to the data.
     *
     * @var array
     */
    public static $rules = array();

    /**
     * The array of custom error messages.
     *
     * @var array
     */
    public static $customMessages = array();

    /**
     * The array of custom attributes.
     *
     * @var array
     */
    public static $customAttributes = array();

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
     */
    public function getModelValidationSettings($uniqueRules = true, $rules = [], $customMessages = [], $customAttributes = [])
    {

        $rules = array_merge(static::$rules,$rules);
        if ($this->getKey() && $uniqueRules) {
            $rules = $this->buildUniqueExclusionRules($rules);
        }

        $validationData = [
            'rules' => $rules,
            'customMessages' => array_merge(static::$customMessages,$customMessages),
            'customAttributes' => array_merge(static::$customAttributes,$customAttributes),
        ];

        return $validationData;

    }


    /*
     * Get a full validator instance for the model
     */
    public function getValidator($data = null, $uniqueRules = true, $rules = [], $customMessages = [], $customAttributes = []) {
        $validatorSettings = $this->getModelValidationSettings($uniqueRules, $rules, $customMessages, $customAttributes);

        $data = $data ?: $this->getAttributes();


        return Validator::make($data, $validatorSettings['rules'], $validatorSettings['customMessages'], $validatorSettings['customAttributes']);
    }


    /**
     * Appends the model ID to the 'unique' rules given. The resulting array can
     * then be fed to a Ardent save so that unchanged values don't flag a validation
     * issue. It can also be used with {@link Illuminate\Foundation\Http\FormRequest}
     * to painlessly validate model requests.
     * Rules can be in either strings with pipes or arrays, but the returned rules
     * are in arrays.
     * @param array $rules
     * @return array Rules with exclusions applied
     */
    public function buildUniqueExclusionRules(array $rules = []) {

        if (!count($rules))
            $rules = static::$rules;

        foreach ($rules as $field => &$ruleset) {
            // If $ruleset is a pipe-separated string, switch it to array
            $ruleset = (is_string($ruleset))? explode('|', $ruleset) : $ruleset;

            foreach ($ruleset as &$rule) {
                if (strpos($rule, 'unique:') === 0) {
                    // Stop splitting at 4 so final param will hold optional where clause
                    $params = explode(',', $rule, 4);

                    $uniqueRules = array();

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
                            $uniqueRules[4] = isset($params[3])? $params[3] : $this->primaryKey;
                        }
                    } else {
                        if (isset($this->id)) {
                            $uniqueRules[3] = $this->id;
                        }
                    }

                    $rule = 'unique:'.implode(',', $uniqueRules);
                }
            }
        }

        return $rules;
    }


}
