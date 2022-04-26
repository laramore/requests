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
use Laramore\Contracts\Eloquent\LaramoreBuilder;
use Laramore\Contracts\Field\AttributeField;
use Laramore\Contracts\Http\Filters\BuilderFilter;
use Laramore\Traits\Http\Filters\{
    HasOperatorParameter, RequiresFieldParameter
};

class Date extends BaseFilter implements BuilderFilter
{
    use HasOperatorParameter, RequiresFieldParameter;

    public function getDefaultParams(): array
    {
        return [
            'field' => null,
            'operator' => ($this->operators[0] ?? '='),
            'value' => null,
        ];
    }

    public function filterBuilder(LaramoreBuilder $builder, Collection $params): ?LaramoreBuilder
    {
        $field = $params->get('field');

        if ($field instanceof AttributeField) {
            $value = $params->get('value');

            if (\is_array($value)) {
                $operator = $params->get('operator'); // TODO: With COLLECTION_TYPE.

                foreach ($value as $subValue) {
                    $builder->where(function ($subBuilder) use ($field, $operator, $subValue) {
                        return $field->where($subBuilder, $operator, $subValue);
                    }, 'or');
                }

                return $builder;
            }

            return $field->where($builder, $params->get('operator'), $value);
        }
    }
}
