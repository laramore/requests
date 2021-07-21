<?php
/**
 * Laramore related model filter.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2021
 * @license MIT
 */

namespace Laramore\Contracts\Http\Filters;

use Illuminate\Support\Collection;
use Laramore\Contracts\Eloquent\LaramoreModel;

interface RelatedFilter
{
    /**
     * Indicate that the filter works on query builders.
     *
     * @param LaramoreModel $relation
     * @param Collection    $params
     * @return LaramoreModel
     */
    public function filterRelated(LaramoreModel $relation, Collection $params): ?LaramoreModel;
}
