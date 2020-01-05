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

use Illuminate\Database\Eloquent\Builder;
use Laramore\Facades\{
    Validations, Rules
};
use Laramore\Interfaces\IsALaramoreModel;

trait HasLaramoreRequest
{
    use InteractsWithBody;

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
    protected $strictBody = true;

    /**
     * Return the model class used to generate rules.
     *
     * @return string
     */
    protected function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * Generate a new model.
     *
     * @return IsALaramoreModel
     */
    public function generateModel(): IsALaramoreModel
    {
        $class = $this->getModelClass();

        return new $class;
    }

    /**
     * Return a filter builder.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    protected function filterModel(Builder $builder): Builder
    {
        return $builder;
    }

    /**
     * Find the model based on the request parameters.
     *
     * @param mixed $value
     *
     * @return IsALaramoreModel|null
     */
    public function findModel($value): ?IsALaramoreModel
    {
        return $this->filterModel($this->generateModel()->newQuery())->findOrFail($value);
    }

    /**
     * Resolve the model.
     *
     * @return IsALaramoreModel
     */
    public function resolveModel(): ?IsALaramoreModel
    {
        if (\count($parameters = $this->route()->parameters())) {
            $values = \array_values($parameters);

            return $this->findModel(\end($values));
        }

        return $this->generateModel();
    }

    /**
     * Indicate if the user is allowed to access to this request.
     *
     * @return boolean
     */
    abstract public function authorize(): bool;

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
                    return $rule !== Rules::required()->native;
                });
            }, $rules);
        }

        return $rules;
    }

    /**
     * Return all accepted inputs.
     *
     * @return array
     */
    protected function allowedBody()
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

        if ($this->strictBody) {
            $keys = \array_diff(\array_keys($this->body()), $this->allowedBody());

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
