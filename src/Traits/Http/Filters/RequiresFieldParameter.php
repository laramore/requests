<?php
/**
 * Simplify field parameter in filters.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2020
 * @license MIT
 */

namespace Laramore\Traits\Http\Filters;

use Illuminate\Support\Collection;

trait RequiresFieldParameter
{
    use HasFieldParameter;

    public function checkValue($value=null, Collection $params=null)
    {
        if (!isset($params['field'])) {
            throw new \Exception('Field required');
        }

        $field = $this->checkField($params['field']);

        return $field->cast($value);
    }
}
