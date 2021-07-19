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

use Illuminate\Support\Arr;
use Laramore\Contracts\Field\ManyRelationField;
use Laramore\Contracts\Field\RelationField;
use Laramore\Facades\Operator;

trait HasLaramoreRelatedRequest
{
    use HasLaramoreRequest {
        HasLaramoreRequest::generateModelQuery as protected generateDetachedModelQuery;
    }

    /**
     * Relation to model.
     *
     * @var array
     */
    protected $related;

    /**
     * Related resolved models.
     *
     * @var array
     */
    protected $relatedModels = [];

    /**
     * Return model related models.
     *
     * @return array
     */
    protected function related(): array
    {
        return $this->related;
    }

    /**
     * Resolve the model.
     *
     * @return LaramoreModel|null
     */
    public function resolveModel()
    {
        $meta = $this->meta();
        $query = $this->generateModelQuery();
        $related = $this->related();
        $parameters = array_reverse($this->route()->parameters(), true);

        if (count($related) === count($parameters)) {
            $model = $this->generateModel();

            // TODO: Set throught relations.

            return $model;
        }

        $key = \array_keys($parameters)[0];
        $value = $parameters[$key];
        unset($parameters[$key]);

        if (count($related) !== count($parameters)) {
            throw new \LogicException('Number of parameters does not match the number of related');
        }

        if (! Arr::isAssoc($related)) {
            $related = array_combine(array_keys($parameters), array_reverse($related));
        } else {
            $related = array_reverse($related);
        }

        $this->related = array_reverse($related);

        foreach ($parameters as $name => $key) {
            $relation = $meta->getField($name, RelationField::class);

            if ($relation instanceof ManyRelationField) {
                throw new \Exception('Cannot resolve many models from a one relation: '.$name);
            }

            $relation->where($query, Operator::equal(), [$key]);
        }

        return $query->findOrFail($value);
    }

    /**
     * Resolve models.
     *
     * @return Collection|null
     */
    public function resolveModels()
    {
        $meta = $this->meta();
        $query = $this->generateModelQuery();
        $related = $this->related();
        $parameters = array_reverse($this->route()->parameters(), true);

        if (count($related) !== count($parameters)) {
            throw new \LogicException('Number of parameters does not match the number of related');
        }

        if (! Arr::isAssoc($related)) {
            $related = array_combine(array_keys($parameters), array_reverse($related));
        } else {
            $related = array_reverse($related);
        }

        $this->related = array_reverse($related);

        foreach ($parameters as $name => $key) {
            $relation = $meta->getField($name, RelationField::class);

            if ($relation instanceof ManyRelationField) {
                throw new \Exception('Cannot resolve many models from a one relation: '.$name);
            }

            $relation->where($query, Operator::equal(), [$key]);
        }

        return $query->get();
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
