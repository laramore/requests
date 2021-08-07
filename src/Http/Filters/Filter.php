<?php
/**
 * Filter to filter values on a specific field.
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
use Laramore\Contracts\Field\ComposedField;
use Laramore\Contracts\Http\Filters\BuilderFilter;
use Laramore\Traits\Http\Filters\HasOperatorParameter;

class Filter extends BaseFilter implements BuilderFilter
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

    public function getField()
    {
        $this->needsToBeOwned();

        return $this->field;
    }

    protected function owned()
    {
        parent::owned();

        $meta = $this->getOwner()->getMeta();

        $this->field = $meta->getField($this->getName());
    }

    public function checkValue($value=null)
    {
        // TODO.

        if ($this->getField() instanceof ComposedField) {
            return explode(',', $value);
        }

        return $value;
    }

    public function filterBuilder(LaramoreBuilder $builder, Collection $params): ?LaramoreBuilder
    {
        return $this->getField()->where($builder, $params->get('operator'), $params->get('value'));
    }
}
