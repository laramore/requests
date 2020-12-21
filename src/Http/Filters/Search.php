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

use Illuminate\Support\Collection;
use Laramore\Contracts\Eloquent\{
    LaramoreModel, LaramoreBuilder, LaramoreCollection
};
use Laramore\Contracts\Field\AttributeField;
use Laramore\Contracts\Field\Field;
use Laramore\Contracts\Http\Filters\BuilderFilter;
use Laramore\Elements\OperatorElement;
use Laramore\Traits\Http\Filters\{
    HasOperatorParameter, HasFieldParameter
};

class Search extends BaseFilter implements BuilderFilter
{
    use HasFieldParameter, HasOperatorParameter;

    protected $allowedBooleans;

    public function getDefaultParams(): array
    {
        return [
            'field' => null,
            'operator' => ($this->operators[0] ?? '='),
            'boolean' => ($this->allowedBooleans[0] ?? 'and'),
        ];
    }

    public function checkValue($value=null)
    {
        return $value;
    }

    protected function checkBoolean($value=null)
    {
        if (!\in_array($value, $this->allowedBooleans)) {
            throw new \Exception('Give right boolean');
        }

        return $value;
    }

    public function filterBuilder(LaramoreBuilder $builder, Collection $params): ?LaramoreBuilder
    {
        $field = $params->get('field');

        if ($field instanceof AttributeField) {
            $value = $params->get('value');

            if (\is_array($value)) {
                $operator = $params->get('operator');

                foreach ($value as $subValue) {
                    $builder->where(function ($subBuilder) use ($field, $operator, $subValue) {
                        return $field->where($subBuilder, $operator, $subValue);
                    }, $params->get('boolean'));
                }

                return $builder;
            }

            return $field->where($builder, $params->get('operator'), $value);
        }
    }

    public function filterCollection(LaramoreCollection $collection, $value, Field $field=null, OperatorElement $operator=null)
    {
        if (!($field instanceof AttributeField)) {
        }
    }

    public function filterModel(LaramoreModel $model, $value, Field $field=null, OperatorElement $operator=null)
    {
        if (!($field instanceof AttributeField)) {
            return $this->filterCollection($model->newCollection([$model]), $field, $value)->first();
        }
    }
}
