<?php
/**
 * Laramore collection filter.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2020
 * @license MIT
 */

namespace Laramore\Contracts\Http\Filters;

use Illuminate\Support\Collection;
use Laramore\Contracts\Eloquent\LaramoreCollection;

interface CollectionFilter
{
    /**
     * Indicate that the filter works on query builders.
     *
     * @param LaramoreCollection $collection
     * @param Collection         $params
     * @return LaramoreCollection
     */
    public function filterCollection(LaramoreCollection $collection, Collection $params): ?LaramoreCollection;
}
