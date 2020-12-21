<?php
/**
 * Laramore builder filter.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2020
 * @license MIT
 */

namespace Laramore\Contracts\Http\Filters;

use Illuminate\Support\Collection;
use Laramore\Contracts\Eloquent\LaramoreBuilder;

interface BuilderFilter
{
    /**
     * Indicate that the filter works on query builders.
     *
     * @param LaramoreBuilder $builder
     * @param Collection      $params
     * @return LaramoreBuilder
     */
    public function filterBuilder(LaramoreBuilder $builder, Collection $params): ?LaramoreBuilder;
}
