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
    Filter, BuilderFilter, CollectionFilter, ModelFilter, RelatedFilter
};
use Laramore\Exceptions\FilterException;

class FilterMeta implements BuilderFilter, CollectionFilter, ModelFilter, RelatedFilter
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

    /**
     * Paginate.
     *
     * @var bool
     */
    protected $paginate = false;

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
        return $this->getRequest()->modelClass()::getMeta();
    }

    public function paginate($paginate = true)
    {
        $this->paginate = $paginate;
    }

    public function doesPaginate()
    {
        return $this->paginate;
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

    public function filterBuilder(LaramoreBuilder $builder, Collection $filters): ?LaramoreBuilder
    {
        foreach ($filters as $filterName => $filterValue) {
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

    public function filterCollection(LaramoreCollection $collection, Collection $filters): ?LaramoreCollection
    {
        foreach ($filters as $filterName => $filterValue) {
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

    public function filterModel(LaramoreModel $model, Collection $filters): ?LaramoreModel
    {
        foreach ($filters as $filterName => $filterValue) {
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

    public function filterRelated(LaramoreModel $model, Collection $filters): ?LaramoreModel
    {
        foreach ($filters as $filterName => $filterValue) {
            $filter = $this->getFilter($filterName);

            if ($filter instanceof RelatedFilter) {
                foreach ($this->buildParams($filterValue) as $filterParams) {
                    $model = $filter->filterRelated($model, $filter->buildParams($filterParams));

                    if (\is_null($model)) {
                        return null;
                    }
                }
            }
        }

        return $model;
    }

    public function setFilter(string $name, Filter $filter)
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
        if (! $this->hasFilter($name)) {
            if ($this->doesPaginate() && in_array($name, ['cursor', 'page'])) return;
            // if ($name === '_method') return; ??

            throw new FilterException($name, "The filter $name does not exist");
        }

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
