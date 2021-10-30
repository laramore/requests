<?php
/**
 * Simplify operator parameter in filters.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2020
 * @license MIT
 */

namespace Laramore\Traits\Http\Filters;

use Illuminate\Support\Collection;
use Laramore\Elements\OperatorElement;
use Laramore\Facades\Operator;

trait HasOperatorParameter
{
    protected $operators;

    public function checkOperator($operator, Collection $params): OperatorElement
    {
        $operator = ($operator ?? '=');

        if (\is_array($this->operators) && !\in_array($operator, $this->operators)) {
            throw new \Error("Wrong operator `$operator`");
        }

        $opElement = Operator::find($operator);

        if ($opElement->needs(OperatorElement::COLLECTION_TYPE)) {
            $params->put('value', collect($params->get('value')));
        }

        return $opElement;
    }
}
