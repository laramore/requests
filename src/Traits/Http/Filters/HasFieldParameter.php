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

trait HasFieldParameter
{
    protected $fields;

    public function checkField($fieldName=null, $params=[])
    {
        if (\is_array($this->fields) && !\in_array($fieldName, $this->fields)) {
            throw new \Exception('Field not allowed');
        }

        return $this->getOwner()->getMeta()->getField($fieldName);
    }
}
