<?php
/**
 * Filter values on a specific field.
 * Ex: show only users with a specific father.
 * Both father or father_id can be used (the composed field or the unique attribute).
 *
 * Classic usage: ?father=specific_uid
 * With operator different: ?father[operator]=!=&father[value]=specific_uid
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2021
 * @license MIT
 */

namespace Laramore\Http\Filters;

use Illuminate\Support\Collection;
use Laramore\Contracts\Eloquent\LaramoreBuilder;
use Laramore\Contracts\Field\{
    Field, ComposedField,
    RelationField
};
use Laramore\Contracts\Http\Filters\BuilderFilter;
use Laramore\Elements\OperatorElement;
use Laramore\Exceptions\FilterException;
use Laramore\Traits\Http\Filters\HasOperatorParameter;

class Related extends BaseFilter implements BuilderFilter
{
    use HasOperatorParameter;

    protected $field;

    public function getDefaultParams(): array
    {
        return [
            'operator' => ($this->operators[0] ?? '='),
            'value' => null,
        ];
    }

    public function getField(): Field
    {
        $this->needsToBeOwned();

        return $this->field;
    }

    protected function owned()
    {
        parent::owned();

        $meta = $this->getOwner()->getMeta();

        $this->field = $meta->getField($this->getName());

        if (! ($this->field instanceof RelationField)) {
            throw new FilterException($this, "The field {$this->getName()} is not a relation field");
        }
    }

    public function checkValue($value=null, Collection $params=null)
    {
        if ($this->getField() instanceof ComposedField) {
            if ($params->get('operator')->needs(OperatorElement::COLLECTION_TYPE)) {
                return $value->map(function ($subValue) {
                    return new Collection(explode(',', $subValue));
                });
            }

            return new Collection(explode(',', $value));
        }

        return $value;
    }

    public function filterBuilder(LaramoreBuilder $builder, Collection $params): ?LaramoreBuilder
    {
        $field = $this->getField();
        $operator = $params->get('operator');
        $method = $operator->getWhereMethod();

        if (method_exists($field, $method) || $field::hasMacro($method)) {
            return \call_user_func([$field, $method], $builder, collect($params->get('value')));
        }

        return $field->where($builder, $operator, $params->get('value'));
    }
}
