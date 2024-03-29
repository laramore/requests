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
use Laramore\Contracts\Eloquent\{
    LaramoreMeta, LaramoreModel
};
use Laramore\Contracts\Field\RelationField;
use Laramore\Eloquent\FilterMeta;
use Laramore\Facades\Option;

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
    protected $pagination;

    /**
     * All inputs.
     *
     * @var array
     */
    protected $inputs;

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

        return $this->filterMeta()->filterBuilder($builder, $this->filters());
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

        return $this->filterMeta()->filterModel($model, $this->filters());
    }

    /**
     * Resolve pagination.
     *
     * @return Collection|null
     */
    public function resolvePagination()
    {
        $query = $this->generateModelQuery();

        if ($this->has('cursor')) {
            return $query->cursorPaginate();
        }

        return $query->paginate();
    }

    /**
     * Resolve models.
     *
     * @return Collection|null
     */
    public function resolveModels()
    {
        if ($this->filterMeta()->doesPaginate()) {
            $pagination = $this->resolvePagination();

            header('Pagination-Count: '. $pagination->perPage());
            header('Pagination-Page: '. $pagination->currentPage());
            header('Pagination-Limit: '. $pagination->lastPage());
            header('Pagination-Total: '. $pagination->total());

            return $pagination->getCollection();
        }

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

        return $this->filterMeta()->filterCollection($collection, $this->filters());
    }

    /**
     * Get page.
     *
     * @return Paginator|mixed
     */
    public function getPagination()
    {
        $pagination = $this->resolvePagination();

        $pagination->setCollection(
            $this->filterMeta()->filterCollection($pagination->getCollection(), $this->filters())
        );

        return $pagination;
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
    public function pagination()
    {
        if (\is_null($this->pagination)) {
            $this->pagination = $this->getPagination();

            $this->models = $this->pagination->getCollection();
        }

        return $this->pagination;
    }

    public static function resolveFieldsFromMeta(LaramoreMeta $meta)
    {
        $fields = $meta->getFields();
        $visibleOption = Option::visible();
        $names = [];

        foreach ($fields as $field) {
            if (! $field->hasOption($visibleOption) || ($field instanceof RelationField)) continue;

            if ($field->getOwner() === $meta || \in_array($field->getOwner(), $fields)) {
                $names[] = $field->getNative();
            }
        }

        return $names;
    }

    /**
     * Return all accepted fields.
     *
     * @return array<string>
     */
    public function fields(): array
    {
        return static::resolveFieldsFromMeta($this->meta());
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
        $keys = $this->allowedBody();
        $meta = $this->meta();

        $rules = [];

        foreach ($keys as $key) {
            $rules = array_merge_recursive(
                $rules, $meta->getField($key)->getRules()
            );
        }

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

            // TODO: Check sub for forbidden.
        }

        return $rules;
    }

    /**
     * Get the validated data from the request.
     *
     * @return array
     */
    public function validated(string $key=null, $default=null)
    {
        $data = parent::validated();

        if (is_null($key)) {
            if (isset($data)) {
                return $data;
            }

            $keys = array_keys(parent::input());
            $data = [];

            foreach ($keys as $key) {
                $data[$key] = $this->input($key);
            }

            return $data;
        }

        if (isset($data[$key])) {
            return $data[$key];
        }

        $value = parent::input($key, $default);

        if (! $this->meta()->hasField($key)) {
            return $value;
        }

        return $this->meta()->getField($key)->cast($value);
    }

    public function filters()
    {
        return $this->filters;
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

        $modelName = Str::camel($this->meta()->getModelName());

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
