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

        throw new \Exception('Only with, without or only');
    }

    public function filterBuilder(LaramoreBuilder $builder, Collection $params): ?LaramoreBuilder
    {
        $value = $params->get('with');

        return $builder->{$value.'Treshed'}();
    }
}
