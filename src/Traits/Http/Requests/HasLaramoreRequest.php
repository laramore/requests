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
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    public function getFilterMeta()
    {
        return $this->filterMeta;
    }

    public function getMeta()
    {
        return $this->getModelClass()::getMeta();
    }

    /**
     * Generate a new model.
     *
     * @return LaramoreModel
     */
    public function generateModel(): LaramoreModel
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
        $builder = $this->generateModel()->newQuery();

        return $this->getFilterMeta()->filterBuilder($builder, $this->filters);
    }

    /**
     * Find the model.
     *
     * @param mixed $value
     *
     * @return LaramoreModel|null
     */
    public function findModel($value)
    {
        $model = $this->generateModelQuery()->findOrFail($value);

        return $this->getFilterMeta()->filterModel($model, $this->filters);
    }

    /**
     * Get models.
     *
     * @return Collection|mixed
     */
    public function getModels()
    {
        $collection = $this->generateModelQuery()->get();

        return $this->getFilterMeta()->filterCollection($collection, $this->filters);
    }

    /**
     * Get page.
     *
     * @return Paginator|mixed
     */
    public function getPaginate()
    {
        $paginate = $this->generateModelQuery()->paginate();
        return $paginate;
        return $this->getFilterMeta()->filterCollection($paginate, $this->filters);
    }

    /**
     * Resolve the model.
     *
     * @return LaramoreModel|mixed
     */
    public function resolveModel()
    {
        if (\count($parameters = $this->route()->parameters()) > 0) {
            $values = \array_values($parameters);

            return $this->findModel(\end($values));
        }

        return $this->generateModel();
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
            $this->model = $this->resolveModel();
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
        $meta = $this->getMeta();
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
        $rules = Validation::getHandler($this->getModelClass())->getRules($this->allowed());

        if (\in_array($this->method(), $this->removeRequired)) {
            return \array_map(function ($fieldRules) {
                return \array_filter($fieldRules, function ($rule) {
                    return !Str::startsWith($rule, $this->requiredRules);
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

        $this->model()->setRawAttributes($this->validated());
    }

    /**
     * Indicate if the user is allowed to access to this request.
     *
     * @return boolean
     */
    abstract public function authorize(): bool;
}
