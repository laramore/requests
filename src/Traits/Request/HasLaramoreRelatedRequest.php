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
    Builder, Collection, Relations\Relation
};
use Laramore\Contracts\Eloquent\LaramoreModel;

trait HasLaramoreRelatedRequest
{
    use HasLaramoreRequest {
        HasLaramoreRequest::generateModelQuery as protected generateDetachedModelQuery;
    }

    /**
     * Based model used to resolve the model of this request.
     *
     * @var string
     */
    protected $baseModelClass;

    /**
     * Relation between base model and the final model.
     *
     * @var string
     */
    protected $modelRelation;

    /**
     * Return the base model class.
     *
     * @return string
     */
    protected function getBaseModelClass(): string
    {
        return $this->baseModelClass;
    }

    /**
     * Generate a new base model.
     *
     * @return LaramoreModel
     */
    public function generateBaseModel(): LaramoreModel
    {
        $class = $this->getBaseModelClass();

        return new $class;
    }

    /**
     * Generate a new base query.
     *
     * @return Builder
     */
    public function generateBaseModelQuery(): Builder
    {
        return $this->generateBaseModel()->newQuery();
    }

    /**
     * Find the base model.
     *
     * @param mixed $value
     *
     * @return LaramoreModel|null
     */
    public function findBaseModel($value): ?LaramoreModel
    {
        return $this->generateBaseModelQuery()->findOrFail($value);
    }

    /**
     * Get base models.
     *
     * @return Collection
     */
    public function getBaseModels(): Collection
    {
        return $this->generateBaseModelQuery()->get();
    }

    /**
     * Resolve the base model.
     *
     * @return LaramoreModel
     */
    public function resolveBaseModel(): ?LaramoreModel
    {
        dd($this->route()->parameters());
        if (\count($parameters = $this->route()->parameters()) > 1) {
            $values = \array_values($parameters);

            return $this->findBaseModel($values[\count($values) - 2]);
        }

        return $this->generateBaseModel();
    }

    /**
     * Return the validated base model.
     *
     * @return LaramoreModel
     */
    public function baseModel(): LaramoreModel
    {
        if (\is_null($this->baseModel)) {
            $this->baseModel = $this->resolveBaseModel();
        }

        return $this->baseModel;
    }

    /**
     * Return the validated base models.
     *
     * @return Collection
     */
    public function baseModels(): Collection
    {
        if (\is_null($this->baseModels)) {
            $this->baseModels = $this->getBaseModels();
        }

        return $this->baseModels;
    }

    /**
     * Generate a new model.
     *
     * @return Relation
     */
    public function generateRelation(LaramoreModel $baseModel): Relation
    {
        return \call_user_func([$baseModel, $this->modelRelation]);
    }

    /**
     * Generate a new query.
     *
     * @return Builder
     */
    public function generateModelQuery(LaramoreModel $baseModel=null): Builder
    {
        if (\is_null($baseModel)) {
            return $this->generateDetachedModelQuery();
        }

        return $this->generateRelation($baseModel);
    }

    /**
     * Find the model based on the request parameters.
     *
     * @param mixed $value
     *
     * @return LaramoreModel|null
     */
    public function findModel($baseValue, $value=null): ?LaramoreModel
    {
        if (\is_null($value)) {
            [$baseValue, $value] = [$value, $baseValue];
        }

        $baseModel = \is_null($baseValue) ? $this->baseModel() : $this->findBaseModel($baseValue);

        return $this->generateRelation($baseModel)->findOrFail($value);
    }

    /**
     * Get models.
     *
     * @return Collection
     */
    public function getModels($baseValue=null): Collection
    {
        $baseModel = \is_null($baseValue) ? $this->baseModel() : $this->findBaseModel($baseValue);

        return $this->generateRelation($baseModel)->get();
    }
}
