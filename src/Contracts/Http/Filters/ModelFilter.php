<?php
/**
 * Laramore model filter.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2020
 * @license MIT
 */

namespace Laramore\Contracts\Http\Filters;

use Illuminate\Support\Collection;
use Laramore\Contracts\Eloquent\LaramoreModel;

interface ModelFilter
{
    /**
     * Indicate that the filter works on query builders.
     *
     * @param LaramoreModel $model
     * @param Collection    $params
     * @return LaramoreModel
     */
    public function filterModel(LaramoreModel $model, Collection $params): ?LaramoreModel;
}
