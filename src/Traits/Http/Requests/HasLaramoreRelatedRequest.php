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

use Illuminate\Support\{
    Arr, Str
};
use Laramore\Contracts\Field\ManyRelationField;
use Laramore\Contracts\Field\RelationField;
use Laramore\Facades\Operator;

trait HasLaramoreRelatedRequest
{
    use HasLaramoreRequest {
        HasLaramoreRequest::generateModelQuery as protected generateDetachedModelQuery;
    }

    /**
     * Root model class for relation.
     *
     * @var array
     */
    protected $rootModelClass;

    /**
     * Related resolved models.
     *
     * @var array
     */
    protected $relatedModels = [];

    /**
     * Return root model.
     *
     * @return string
     */
    public function rootModelClass(): string
    {
        return $this->rootModelClass;
    }

    /**
     * Return model related models.
     *
     * @return array
     */
    public function relatedModels(): array
    {
        return $this->relatedModels;
    }

    /**
     * Resolve the model.
     *
     * @return LaramoreModel|null
     */
    public function resolveModel()
    {
        $parameters = $this->route()->parameters();
        $keys = array_keys($parameters);
        $name = end($keys);
        $value = $parameters[$name];
        unset($parameters[$name]);

        $related = null;

        foreach ($parameters as $name => $key) {
            $pastRelated = $related;

            if (is_null($related)) {
                $related = $this->rootModelClass()::findOrFail($key);
            } else if ($related::getMeta()->hasField($name, RelationField::class)) {
                $related = $related->newRelationQuery($name)->findOrFail($key);
            } else {
                $name = Str::plural($name);

                $related = $related->newRelationQuery($name)->findOrFail($key);
            }

            $related = $this->filterMeta()->filterRelated($related, $this->filters());
            $related->setRelationValue($name, $pastRelated);

            $this->relatedModels[$name] = $related;
        }

        if (is_null($related)) {
            throw new \LogicException(static::class.' must be related');
        }

        $meta = $this->meta();

        if ($meta->hasField($name, RelationField::class)) {
            $reversedName = $meta->getField($name)->getReversedField()->getName();
        } else {
            $name = Str::plural($name);

            $reversedName = $meta->getField($name)->getReversedField()->getName();
        }

        // TODO: Filter builder.
        // return $this->filterMeta()->filterBuilder($related->newRelationQuery($reversedName), $this->filters())->get()
        return $related->newRelationQuery($reversedName)->findOrFail($value)
            ->setRelationValue($name, $related);
    }

    /**
     * Resolve models.
     *
     * @return Collection|null
     */
    public function resolveModels()
    {
        $parameters = $this->route()->parameters();
        $related = null;

        foreach ($parameters as $name => $key) {
            $pastRelated = $related;

            if (is_null($related)) {
                $related = $this->rootModelClass()::findOrFail($key);
            } else if ($related::getMeta()->hasField($name, RelationField::class)) {
                $related = $related->newRelationQuery($name)->findOrFail($key);
            } else {
                $name = Str::plural($name);

                $related = $related->newRelationQuery($name)->findOrFail($key);
            }

            $related = $this->filterMeta()->filterRelated($related, $this->filters());
            $related->setRelationValue($name, $pastRelated);

            $this->relatedModels[$name] = $related;
        }

        if (is_null($related)) {
            throw new \LogicException(static::class.' must be related');
        }

        $meta = $this->meta();
        $keys = array_keys($parameters);
        $name = end($keys);

        if ($meta->hasField($name, RelationField::class)) {
            $reversedName = $meta->getField($name)->getReversedField()->getName();
        } else {
            $name = Str::plural($name);

            $reversedName = $meta->getField($name)->getReversedField()->getName();
        }

        // TODO: Filter builder.
        // return $this->filterMeta()->filterBuilder($related->newRelationQuery($reversedName), $this->filters())->get()
        return $related->newRelationQuery($reversedName)->get()
            ->each->setRelationValue($name, $related);
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
    // public function __call($method, $parameters)
    // {
    //     if (static::hasMacro($method)) {
    //         return parent::__call($method, $parameters);
    //     }

    //     $modelName = $this->meta()->getModelName();

    //     if ($method === $modelName) {
    //         return $this->model();
    //     }

    //     if ($method === Str::plural($modelName)) {
    //         return $this->models();
    //     }

    //     return parent::__call($method, $parameters);
    // }
}
