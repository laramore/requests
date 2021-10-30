<?php
/**
 * Filter for pagination.
 * Nothing is required, it is done by Laramore
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2020
 * @license MIT
 */

namespace Laramore\Http\Filters;

class Page extends BaseFilter
{
    public function getDefaultParams(): array
    {
        return [
            'value' => 1,
        ];
    }

    public function checkValue($value)
    {
        return (int) $value;
    }
}
