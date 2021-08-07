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
use Laramore\Contracts\Field\RelationField;

trait HasLaramoreRelatedRequest
{
    use HasLaramoreRequest {
        HasLaramoreRequest::generateModelQuery as protected generateDetachedModelQuery;
    }

    /**
     * Root model class for relation.
     *
     * @var string
     */
    protected $rootModelClass;

    /**
     * Related relation name.
     *
     * @var string
     */
    protected $relatedName;

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

    public function relatedModel(string $name=null)
    {
        if (! is_null($name)) {
            return $this->relatedModels()[$name];
        }

        return $this->relatedModels()[$name];
    }

    public function relatedName()
    {
        if (! is_null($this->relatedName)) {
            return $this->relatedName;
        }

        $meta = $this->meta();
        $names = array_keys($this->route()->parameters());
        $name = array_pop($names);

        if ($meta->hasField($name, RelationField::class)) {
            return $this->relatedName = $name;
        }

        $name = Str::plural($name);

        if ($meta->hasField($name, RelationField::class)) {
            return $this->relatedName = $name;
        }

        $name = array_pop($names);

        if ($meta->hasField($name, RelationField::class)) {
            return $this->relatedName = $name;
        }

        $name = Str::plural($name);

        if ($meta->hasField($name, RelationField::class)) {
            return $this->relatedName = $name;
        }

        throw new \Exception('Related name required for resolution');
    }

    public function relatedField()
    {
        return $this->meta()->getField($this->relatedName());
    }

    /**
     * Return all accepted fields.
     *
     * @return array<string>
     */
    public function fields(): array
    {
        $fields = parent::fields();
        $relatedField = $this->relatedField();
        $fieldsToRemove = [$relatedField->getName()];
        $decomposed = $relatedField->decompose();

        if (isset($decomposed[$this->modelClass()])) {
            $fieldsToRemove = array_merge($fieldsToRemove, array_map(function ($field) {
                return $field->getName();
            }, $decomposed[$this->modelClass()]));
        }

        foreach ($fieldsToRemove as $name) {
            if (($key = array_search($name, $fields)) !== false) {
                unset($fields[$key]);
            }
        }

        return array_values($fields);
    }

    /**
     * Resolve the model.
     *
     * @return LaramoreModel|null
     */
    public function resolveModel()
    {
        $parameters = $this->route()->parameters();
        $names = array_keys($parameters);
        $related = null;

        while ($name = array_shift($names)) {
            $pastRelated = $related;
            $key = $parameters[$name];

            if (is_null($related)) {
                // Is null, we start from root model.
                $related = $this->rootModelClass()::findOrFail($key);
            } else {
                // We keep going throught relations.
                if ($related::getMeta()->hasField($name, RelationField::class)) {
                    $related = $related->newRelationQuery($name)->findOrFail($key);
                } else {
                    $related = $related->newRelationQuery(Str::plural($name))->findOrFail($key);
                }
            }

            $related = $this->filterMeta()->filterRelated($related, $this->filters());
            $related->setRelation($name, $pastRelated);

            $this->relatedModels[$name] = $related;

            // If it is the last parameter, we need to check the model class.
            if (count($names) === 1) {
                $field = $this->relatedField();
                $reversedField = $field->getReversedField();

                // If we just found it, find or fail it.
                if (get_class($related) === $reversedField->getMeta()->getModelClass()) {
                    // TODO: Filter builder.
                    return $related->newRelationQuery($reversedField->getName())->findOrFail($parameters[$names[0]])
                        ->setRelation($field->getName(), $related);
                }
            }
        }

        if (is_null($related)) {
            throw new \LogicException(static::class.' must be related');
        }

        return $this->generateModel()->setRelation($this->relatedName(), $related);
    }

    /**
     * Resolve models.
     *
     * @return Collection|null
     */
    public function resolveModels()
    {
        $parameters = $this->route()->parameters();
        $names = array_keys($parameters);
        $related = null;

        while ($name = array_shift($names)) {
            $pastRelated = $related;
            $key = $parameters[$name];

            if (is_null($related)) {
                // Is null, we start from root model.
                $related = $this->rootModelClass()::findOrFail($key);
            } else {
                // We keep going throught relations.
                if ($related::getMeta()->hasField($name, RelationField::class)) {
                    $related = $related->newRelationQuery($name)->findOrFail($key);
                } else {
                    $related = $related->newRelationQuery(Str::plural($name))->findOrFail($key);
                }
            }

            $related = $this->filterMeta()->filterRelated($related, $this->filters());
            $related->setRelation($name, $pastRelated);

            $this->relatedModels[$name] = $related;
        }

        if (is_null($related)) {
            throw new \LogicException(static::class.' must be related');
        }

        // TODO: Filter builder.
        return $related->newRelationQuery($this->relatedField()->getReversedField()->getName())->get();
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

        $models = \array_keys($this->relatedModels());

        if (in_array($snakeName = Str::snake($method), $models)) {
            return $this->relatedModel($snakeName);
        }

        return parent::__call($method, $parameters);
    }
}
