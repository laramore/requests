<?php
/**
 * Filter exception class.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2021
 * @license MIT
 */

namespace Laramore\Exceptions;

use Illuminate\Support\Arr;
use Laramore\Contracts\Http\Filters\Filter;

class FilterException extends LaramoreException
{
    protected $filter;

    protected $errors;

    public function __construct(Filter|string $filter, $errors, int $code=400)
    {
        $this->filter = $filter;
        $this->errors = Arr::wrap($errors);

        parent::__construct("Filter {$this->name()} excepted: ".implode('. ', $this->errors), $code);
    }

    /**
     * Return the filter that threw the exception.
     *
     * @return Filter|string
     */
    public function filter(): Filter|string
    {
        return $this->filter;
    }

    /**
     * Return the filter name that threw the exception.
     *
     * @return Filter|string
     */
    public function name(): string
    {
        if ($this->filter instanceof Filter) {
            return $this->filter->getName();
        }

        return $this->filter;
    }


    /**
     * Return the filter that threw the exception.
     *
     * @return array
     */
    public function errors(): array
    {
        return [
            $this->name() => $this->errors,
        ];
    }
}
