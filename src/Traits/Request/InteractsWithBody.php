<?php
/**
 * Simplify interactions with request body.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2019
 * @license MIT
 */

namespace Laramore\Traits\Request;

use Illuminate\Support\Arr;

trait InteractsWithBody
{
    /**
     * Retrieve an input item from the request body.
     * The body is retrieved only if it is a json content
     * or if it's not been sent in "GET" or "HEAD" method
     *
     * @param  string|null       $key
     * @param  string|array|null $default
     * @return string|array|null
     */
    public function body($key=null, $default=null)
    {
        if (\is_null($this->body)) {
            if ($this->isJson()) {
                $this->body = $this->json();
            } else {
                $this->body = \in_array($this->getRealMethod(), ['GET', 'HEAD']) ? [] : $this->request;
            }
        }

        return Arr::get($this->body->all(), $key, $default);
    }
}