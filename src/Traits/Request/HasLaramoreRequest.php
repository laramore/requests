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

use Illuminate\Database\Eloquent\{
    Builder, Collection
};
use Laramore\Facades\{
    Validations, Rule
};
use Laramore\Interfaces\IsALaramoreModel;
use Laramore\Validations\Rule\Forbidden;

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
     * Models resolved from the database.
     *
     * @var Collection
     */
    protected $models;

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
     * Generate a new query.
     *
     * @return Builder
     */
    public function generateModelQuery(): Builder
    {
        return $this->generateModel()->newQuery();
    }

    /**
     * Find the model.
     *
     * @param mixed $value
     *
     * @return IsALaramoreModel|null
     */
    public function findModel($value): ?IsALaramoreModel
    {
        return $this->generateModelQuery()->findOrFail($value);
    }

    /**
     * Get models.
     *
     * @return Collection
     */
    public function getModels(): Collection
    {
        return $this->generateModelQuery()->get();
    }

    /**
     * Resolve the model.
     *
     * @return IsALaramoreModel
     */
    public function resolveModel(): ?IsALaramoreModel
    {
        if (\count($parameters = $this->route()->parameters()) > 0) {
            $values = \array_values($parameters);

            return $this->findModel(\end($values));
        }

        return $this->generateModel();
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
     * Return the validated model.
     *
     * @return Collection
     */
    public function models(): Collection
    {
        if (\is_null($this->models)) {
            $this->models = $this->getModels();
        }

        return $this->models;
    }

    /**
     * Return all accepted fields.
     *
     * @return array<string>
     */
    public function fields(): array
    {
        return $this->getModelClass()::getMeta()->getRequiredFieldNames();
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
     * Return all accepted body values.
     *
     * @return array
     */
    public function allowed()
    {
        $allowedKeys = \array_fill_keys($this->allowedBody(), null);

        return \array_merge($allowedKeys, \array_intersect_key($this->body(), $allowedKeys));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = Validations::getHandler($this->getModelClass())->getRules($this->allowed());
        $required = Rule::required()->native;

        if (\in_array($this->method(), $this->removeRequired)) {
            return \array_map(function ($fieldRules) use ($required) {
                return \array_filter($fieldRules, function ($rule) use ($required) {
                    return $rule !== $required;
                });
            }, $rules);
        }

        if ($this->strictBody) {
            $keys = \array_diff(\array_keys($this->body()), $this->allowedBody());

            if (\count($keys)) {
                $rules = \array_merge($rules, \array_fill_keys($keys, ['forbidden']));
            }
        }

        return $rules;
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

    /**
     * Indicate if the user is allowed to access to this request.
     *
     * @return boolean
     */
    abstract public function authorize(): bool;
}
