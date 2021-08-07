<?php
/**
 * Laramore filter.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2021
 * @license MIT
 */

namespace Laramore\Contracts\Http\Filters;

use Illuminate\Support\Collection;
use Laramore\Contracts\{
    Configured, Locked, Owned
};

interface Filter extends Configured, Owned, Locked
{
    /**
     * Call the constructor and generate the filter.
     *
     * @param  array $properties
     * @return self
     */
    public static function filter(array $properties=[]);

    /**
     * Return the configuration for this filter.
     *
     * @param string $path
     * @param mixed  $default
     * @return mixedf
     */
    public function getConfig(string $path=null, $default=null);

    /**
     * Return the type object of the field.
     *
     * @return string
     */
    public function getName(): string;

    public function checkValue($value=null);

    public function buildParams($params): Collection;
}
