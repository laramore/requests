<?php
/**
 * Trait for automating model requests.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2019
 * @license MIT
 */

namespace Laramore\Traits\Http\Requests;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\{
    Builder, Collection
};
use Illuminate\Pagination\Paginator;
use Laramore\Facades\Validation;
use Laramore\Contracts\Eloquent\LaramoreModel;
use Laramore\Eloquent\FilterMeta;

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
     * @var LaramoreModel
     */
    protected $model;

    /**
     * Models resolved from the database.
     *
     * @var Collection
     */
    protected $models;

    /**
     * Paged models resolved from the database.
     *
     * @var Collection
     */
    protected $paginate;

    /**
     * All filters.
     *
     * @var Collection
     */
    protected $filters;

    /**
     * Options only apply on defined values.
     * Only 'POST' and 'PUT' need all required values actually.
     *
     * @var array
     */
    protected $removeRequired = [
        self::METHOD_HEAD, self::METHOD_GET, self::METHOD_PATCH, self::METHOD_DELETE
    ];

    protected $requiredRules = [
        'required', 'required_unless', 'required_without', 'requied_without_all',
    ];

    /**
     * Force to only accept model attributes.
     *
     * @var bool
     */
    protected $strictBody = true;

    /**
     * Return the model class used to generate options.
     *
     * @return string
     */
    public function modelClass(): string
    {
        return $this->modelClass;
    }

    public function filterMeta()
    {
        return $this->filterMeta;
    }

    public function meta()
    {
        return $this->modelClass()::getMeta();
    }

    /**
     * Generate a new model.
     *
     * @return LaramoreModel
     */
    public function generateModel(): LaramoreModel
    {
        $class = $this->modelClass();

        return new $class;
    }

    /**
     * Generate a new query.
     *
     * @return Builder
     */
    public function generateModelQuery(): Builder
    {
        $builder = $this->generateModel()->newQuery();

        return $this->filterMeta()->filterBuilder($builder, $this->filters);
    }

    /**
     * Resolve the model.
     *
     * @return LaramoreModel|null
     */
    public function resolveModel()
    {
        $parameters = $this->route()->parameters();

        if (\count($parameters) > 0) {
            $values = \array_values($parameters);

            return $this->generateModelQuery()->findOrFail(\end($values));
        }

        return $this->generateModel();
    }

    /**
     * Get model.
     *
     * @return LaramoreModel|null
     */
    public function getModel()
    {
        $model = $this->resolveModel();

        if ($model) {
            $model->setRawAttributes($this->validated());
        }

        return $this->filterMeta()->filterModel($model, $this->filters);
    }

    /**
     * Resolve models.
     *
     * @return Collection|null
     */
    public function resolveModels()
    {
        return $this->generateModelQuery()->get();
    }

    /**
     * Get models.
     *
     * @return Collection|null
     */
    public function getModels()
    {
        $collection = $this->resolveModels();

        return $this->filterMeta()->filterCollection($collection, $this->filters);
    }

    /**
     * Get page.
     *
     * @return Paginator|mixed
     */
    public function getPaginate()
    {
        $paginate = $this->generateModelQuery()->paginate();

        return $this->filterMeta()->filterCollection($paginate, $this->filters);
    }

    /**
     * Define all filters of this request.
     *
     * @param FilterMeta $meta
     * @return void
     */
    public static function filter(FilterMeta $meta)
    {
        // Filters defined by user.
    }

    /**
     * Retrieve an input item from the request.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function input($key = null, $default = null)
    {
        if (is_null($key)) {
            $all = parent::input($key, $default);

            foreach ($all as $key => $value) {
                $all[$key] = $this->meta()->getField($key)->cast($value);
            }

            return $all;
        }

        return $this->meta()->getField($key)->cast(parent::input($key, $default));
    }

    /**
     * Return the validated model.
     *
     * @return LaramoreModel|mixed
     */
    public function model()
    {
        if (\is_null($this->model)) {
            $this->model = $this->getModel();
        }

        return $this->model;
    }

    /**
     * Return the validated models.
     *
     * @return Collection|mixed
     */
    public function models()
    {
        if (\is_null($this->models)) {
            $this->models = $this->getModels();
        }

        return $this->models;
    }

    /**
     * Return the validated model page.
     *
     * @return Paginator|mixed
     */
    public function paginate()
    {
        if (\is_null($this->paginate)) {
            $this->paginate = $this->getPaginate();
        }

        return $this->paginate;
    }

    /**
     * Return all accepted fields.
     *
     * @return array<string>
     */
    public function fields(): array
    {
        $meta = $this->meta();
        $requiredFields = $meta->getFieldsWithOption('required');
        $requiredFieldNames = [];

        foreach ($requiredFields as $field) {
            if ($field->getOwner() === $meta || \in_array($field->getOwner(), $requiredFields)) {
                $requiredFieldNames[] = $field->getNative();
            }
        }

        return $requiredFieldNames;
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
     * Get the validation options that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = Validation::getHandler($this->modelClass())->getRules($this->allowed(), true);

        if (\in_array($this->method(), $this->removeRequired)) {
            return \array_map(function ($fieldRules) {
                return \array_filter($fieldRules, function ($rule) {
                    return ! is_string($rule) || ! Str::startsWith($rule, $this->requiredRules);
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

    protected function generateFilter()
    {
        $this->filterMeta = new FilterMeta($this);

        static::filter($this->filterMeta);
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        $this->filters = collect($this->query());
        if ($this->has('_filters')) {
            $this->filters = $this->filters->merge($this->input('_filters'));

            $this->offsetUnset('_filters');
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

        $this->generateFilter();
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return parent::__call($method, $parameters);
        }

        $modelName = $this->meta()->getModelName();

        if ($method === $modelName) {
            return $this->model();
        }

        if ($method === Str::plural($modelName)) {
            return $this->models();
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Indicate if the user is allowed to access to this request.
     *
     * @return boolean
     */
    abstract public function authorize(): bool;
}
