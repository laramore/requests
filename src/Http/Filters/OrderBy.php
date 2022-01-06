<?php
/**
 * Order by filter.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2020
 * @license MIT
 */

namespace Laramore\Http\Filters;

use Illuminate\Support\Collection;
use Laramore\Contracts\Eloquent\{
    LaramoreBuilder, LaramoreCollection
};
use Laramore\Contracts\Field\AttributeField;
use Laramore\Contracts\Http\Filters\{
    BuilderFilter, CollectionFilter
};
use Laramore\Exceptions\FilterException;
use Laramore\Traits\Http\Filters\HasFieldParameter;

class OrderBy extends BaseFilter implements BuilderFilter, CollectionFilter
{
    use HasFieldParameter {
        HasFieldParameter::checkField as public getAndCheckField;
    }

    protected $allowedValues;

    public function getDefaultParams(): array
    {
        return [
            'field' => null,
            'value' => ($this->allowedValues[0] ?? 'asc'),
        ];
    }

    public function checkValue($value=null, Collection $params=null)
    {
        if (\in_array($value, $this->allowedValues)) {
            if ($value === 'random' && !\is_null($params['field'])) {
                throw new FilterException($this, 'Cannot be random and have a field');
            }

            return $value;
        }

        if (\is_array($value)) {
            return \array_map(function ($subValue) {
                return $this->checkValue($subValue);
            }, $value);
        }

        throw new FilterException($this, 'Use right value');
    }

    public function checkField($fieldName=null, array $params=[])
    {
        if (\is_null($fieldName) && $params['value'] === 'random') {
            return null;
        }

        if (\is_array($fieldName)) {
            return \array_map(function ($subName) use ($params) {
                return $this->getAndCheckField($subName, $params);
            }, $fieldName);
        }

        return $this->getAndCheckField($fieldName, $params);
    }

    public function filterBuilder(LaramoreBuilder $builder, Collection $params): ?LaramoreBuilder
    {
        $value = $params->get('value');

        if ($value === 'random') {
            return $builder->inRandomOrder();
        }

        $fields = $params->get('field');
        $fields = \is_array($fields) ? $fields : [$fields];

        foreach ($fields as $index => $field) {
            if ($field instanceof AttributeField) {
                $builder->orderBy($field->getNative(), \is_array($value) ? $value[$index] : $value);
            }
        }

        return $builder;
    }

    public function filterCollection(LaramoreCollection $collection, Collection $params): ?LaramoreCollection
    {
        $value = $params->get('value');
        $fields = $params->get('field');
        $fields = \is_array($fields) ? $fields : [$fields];

        foreach ($fields as $index => $field) {
            if (!($field instanceof AttributeField)) {
                $subValue = \is_array($value) ? $value[$index] : $value;

                $collection->{$subValue === 'asc' ? 'sortBy' : 'sortByDesc'}($field->getNative());
            }
        }

        return $collection;
    }
}
