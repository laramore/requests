<?php
/**
 * Trait for automating model requests.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2019
 * @license MIT
 */

namespace Laramore\Traits\Request;

use Laramore\Facades\Validations;
use Laramore\Interfaces\IsALaramoreModel;

trait HasLaramoreRequest
{
    /**
     * Define the model class used for this request.
     *
     * @var string
     */
    protected $modelClass;

    /**
     * Model with the validated values.
     *
     * @var IsALaramoreModel
     */
    protected $model;

    /**
     * Rules only apply on defined values.
     * Only 'POST' and 'PUT' need all required values actually.
     *
     * @var array
     */
    protected $removeRequired = [
        self::METHOD_HEAD, self::METHOD_GET, self::METHOD_PATCH,
    ];

    /**
     * Force to only accept model attributes.
     *
     * @var bool
     */
    protected $strictParams = true;

    /**
     * Resolve the model.
     *
     * @return IsALaramoreModel
     */
    public function resolveModel(): IsALaramoreModel
    {
        if (\count($parameters = $this->route()->parameters())) {
            return $this->modelClass::find((\array_values($parameters)[0]));
        }

        return new $this->modelClass;
    }

    /**
     * Indicate if the user is allowed to access to this request.
     *
     * @return boolean
     */
    abstract public function authorize(): bool;

    /**
     * Return the model class used to generate rules.
     *
     * @return IsALaramoreModel
     */
    protected function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * Return the validated model.
     *
     * @return IsALaramoreModel
     */
    public function model(): IsALaramoreModel
    {
        if (\is_null($this->model)) {
            $this->model = $this->resolveModel();
        }

        return $this->model;
    }

    /**
     * Return all accepted fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return \array_map(function ($field) {
            return $field->getNative();
        }, \array_filter($this->getModelClass()::getMeta()->getAttributes(), function ($field) {
            return $field->fillable;
        }));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = Validations::getHandler($this->getModelClass())->getRules($this->fields());

        if (\in_array($this->method(), $this->removeRequired)) {
            return \array_map(function ($fieldRules) {
                return \array_filter($fieldRules, function ($rule) {
                    return $rule !== 'required';
                });
            }, $rules);
        }

        return $rules;
    }

    /**
     * Return all accepted params.
     *
     * @return array
     */
    protected function getAllowedParams()
    {
        return $this->fields();
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        if ($this->strictParams) {
            $keys = \array_diff(\array_keys($this->input()), $this->getAllowedParams());

            if (\count($keys)) {
                $validator = $this->getValidatorInstance();

                foreach ($keys as $key) {
                    $validator->errors()->add($key, "The $key field is not allowed");
                }

                $this->failedValidation($validator);
            }
        }
    }

    /**
     * Validate the class instance.
     *
     * @return void
     */
    public function validateResolved()
    {
        parent::validateResolved();

        $this->model()->setAttributes($this->validated());
    }
}
