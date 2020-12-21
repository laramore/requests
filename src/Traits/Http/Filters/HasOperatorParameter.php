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

use Laramore\Elements\OperatorElement;
use Laramore\Facades\Operator;

trait HasOperatorParameter
{
    protected $operators;

    public function checkOperator($operator=null): OperatorElement
    {
        $operator = ($operator ?? '=');

        if (\is_array($this->operators) && !\in_array($operator, $this->operators)) {
            throw new \Error("Wrong operator `$operator`");
        }

        return Operator::find($operator);
    }
}
