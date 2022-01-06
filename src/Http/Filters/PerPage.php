<?php
/**
 * Set the number of models per.
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

class PerPage extends BaseFilter implements BuilderFilter
{
    protected $min;

    protected $max;

    public function getDefaultParams(): array
    {
        $modelClass = $this->getOwner()->getMeta()->getModelClass();

        return [
            'value' => (new $modelClass)->per_page,
        ];
    }

    public function checkValue($value=null)
    {
        $perPage = (int) $value;

        if ($perPage < $this->min || $perPage > $this->max) {
            throw new FilterException($this, "Min per page `{$this->min}` and max `{$this->max}`");
        }

        return $perPage;
    }

    public function filterBuilder(LaramoreBuilder $builder, Collection $params): ?LaramoreBuilder
    {
        $builder->getModel()->setPerPage($params->get('value'));

        return $builder;
    }
}
