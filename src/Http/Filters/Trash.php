<?php
/**
 * Filter to show trashed models.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2020
 * @license MIT
 */

namespace Laramore\Http\Filters;

use Illuminate\Support\Collection;
use Laramore\Contracts\Eloquent\LaramoreBuilder;
use Laramore\Contracts\Http\Filters\BuilderFilter;
use Laramore\Exceptions\FilterException;

class Trash extends BaseFilter implements BuilderFilter
{
    protected $allowedValues;

    public function getDefaultParams(): array
    {
        return [
            'value' => 'with',
        ];
    }

    public function checkValue($value=null)
    {
        if (\in_array($value, $this->allowedValues)) {
            return $value;
        }

        throw new FilterException($this, "{$value} is not allowed for filter. Use only ".\implode(', ', $this->allowedValues));
    }

    public function filterBuilder(LaramoreBuilder $builder, Collection $params): ?LaramoreBuilder
    {
        $value = $params->get('value');

        return $builder->{$value.'Trashed'}();
    }
}
