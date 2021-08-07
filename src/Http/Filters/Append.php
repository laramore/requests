<?php
/**
 * Base filter.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2020
 * @license MIT
 */

namespace Laramore\Http\Filters;

use Laramore\Contracts\Eloquent\{
    LaramoreBuilder, LaramoreModel, LaramoreCollection
};
use Illuminate\Support\Collection;
use Laramore\Contracts\Field\RelationField;
use Laramore\Contracts\Http\Filters\{
    BuilderFilter, CollectionFilter, ModelFilter
};
use Laramore\Exceptions\FilterException;
use Laramore\Traits\Http\Filters\RequiresFieldParameter;

class Append extends BaseFilter implements BuilderFilter, CollectionFilter, ModelFilter
{
    use RequiresFieldParameter;

    protected $allowedValues;

    public function getDefaultParams(): array
    {
        return [
            'field' => null,
            'value' => ($this->allowedValues[0] ?? 'true'),
            'count' => 'false',
        ];
    }

    public function checkValue($value=null)
    {
        if (!\in_array($value, $this->allowedValues)) {
            throw new FilterException($this, 'Give a right boolean');
        }

        return $value === 'true';
    }

    public function filterBuilder(LaramoreBuilder $builder, Collection $params): LaramoreBuilder
    {
        $field = $params->get('field');

        if ($field instanceof RelationField) {
            return $builder->with($field->getName());
        }

        return $builder;
    }

    public function filterCollection(LaramoreCollection $collection, Collection $params): LaramoreCollection
    {
        return $collection->{$params->get('value') ? 'makeVisible' : 'makeHidden'}([$params->get('field')->getName()]);
    }

    public function filterModel(LaramoreModel $model, Collection $params): LaramoreModel
    {
        return $this->filterCollection($model->newCollection([$model]), $params->get('field'), $params->get('value'))->first();
    }
}
