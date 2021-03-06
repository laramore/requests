<?php
/**
 * Filter meta.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2020
 * @license MIT
 */

namespace Laramore\Eloquent;

use Illuminate\Http\Request;
use Illuminate\Support\{
    Arr, Collection, Str
};
use Laramore\Contracts\Eloquent\{
    LaramoreBuilder, LaramoreCollection, LaramoreMeta, LaramoreModel
};
use Laramore\Contracts\Http\Filters\{
    BuilderFilter, CollectionFilter, ModelFilter
};
use Laramore\Http\Filters\BaseFilter;

class FilterMeta implements BuilderFilter, CollectionFilter, ModelFilter
{
    /**
     * Request used by this filter.
     *
     * @var Request
     */
    protected $request;

    /**
     * All filters applied.
     *
     * @var array
     */
    protected $filters = [];

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getMeta(): LaramoreMeta
    {
        return $this->getRequest()->getMeta();
    }

    public function buildParams($params): Collection
    {
        // For example: ?filter=value (one call for the filter with one single value).
        if (!\is_array($params)) {
            return collect([['value' => $params]]);
        }

        // For example: ?filter[field]=name&filter[value]=value&filter[operator]=equal.
        if (Arr::isAssoc($params)) {
            return collect([$params]);
        }

        // For example: ?filter[0][field]=name0&filter[0][value]=value
        return collect($params);
    }

    public function filterBuilder(LaramoreBuilder $builder, Collection $params): ?LaramoreBuilder
    {
        foreach ($params as $filterName => $filterValue) {
            $filter = $this->getFilter($filterName);

            if ($filter instanceof BuilderFilter) {
                foreach ($this->buildParams($filterValue) as $filterParams) {
                    $builder = $filter->filterBuilder($builder, $filter->buildParams($filterParams));

                    if (\is_null($builder)) {
                        return null;
                    }
                }
            }
        }

        return $builder;
    }

    public function filterCollection(LaramoreCollection $collection, Collection $params): ?LaramoreCollection
    {
        foreach ($params as $filterName => $filterValue) {
            $filter = $this->getFilter($filterName);

            if ($filter instanceof CollectionFilter) {
                foreach ($this->buildParams($filterValue) as $filterParams) {
                    $collection = $filter->filterCollection($collection, $filter->buildParams($filterParams));

                    if (\is_null($collection)) {
                        return null;
                    }
                }
            }
        }

        return $collection;
    }

    public function filterModel(LaramoreModel $model, Collection $params): ?LaramoreModel
    {
        foreach ($params as $filterName => $filterValue) {
            $filter = $this->getFilter($filterName);

            if ($filter instanceof ModelFilter) {
                foreach ($this->buildParams($filterValue) as $filterParams) {
                    $model = $filter->filterModel($model, $filter->buildParams($filterParams));

                    if (\is_null($model)) {
                        return null;
                    }
                }
            }
        }

        return $model;
    }

    public function setFilter(string $name, BaseFilter $filter)
    {
        // $this->needsToBeUnlocked();
        $filter->ownedBy($this, Str::snake($name));

        $this->filters[$filter->getName()] = $filter;

        return $this;
    }

    public function hasFilter(string $name)
    {
        return isset($this->filters[$name]);
    }

    public function getFilter(string $name)
    {
        return $this->filters[$name];
    }

    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Return the  with a given name.
     *
     * @param  string $name
     * @return
     */
    public function __get(string $name)
    {
        return $this->getFilter($name);
    }

    /**
     * Set a  with a given name.
     *
     * @param string $name
     * @param   $value
     * @return self
     */
    public function __set(string $name, $value)
    {
        return $this->setFilter($name, $value);
    }
}
