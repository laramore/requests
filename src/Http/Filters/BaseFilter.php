<?php
/**
 * Base filter.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2020
 * @license MIT
 */

namespace Laramore\Http\Filters;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laramore\Contracts\Http\Filters\Filter;
use Laramore\Exceptions\FilterException;
use Laramore\Traits\{
    HasLockedMacros, HasProperties, IsLocked, IsOwned
};

abstract class BaseFilter implements Filter
{
    use IsLocked, IsOwned, HasLockedMacros, HasProperties {
        HasLockedMacros::__call as protected callMacro;
        HasProperties::__call as protected callProperty;
    }

    protected $name;

    protected $defaultParams = [];

    /**
     * Create a filter with bases properties.
     *
     * @param array $properties
     */
    protected function __construct(array $properties=[])
    {
        $this->initProperties(\array_merge(
            $this->getConfig(null, []),
            $properties
        ));
    }

    /**
     * Call the constructor and generate the filter.
     *
     * @param  array $properties
     * @return self
     */
    public static function filter(array $properties=[])
    {
        $creating = Event::until('filters.creating', static::class, \func_get_args());

        if ($creating === false) {
            return null;
        }

        $filter = $creating ?: new static($properties);

        Event::dispatch('filters.created', $filter);

        return $filter;
    }

    /**
     * Return the configuration path for this filter.
     *
     * @param string $path
     * @return mixed
     */
    public function getConfigPath(string $path=null)
    {
        return 'filter.configurations.'.static::class.(\is_null($path) ? '' : '.'.$path);
    }

    /**
     * Return the configuration for this filter.
     *
     * @param string $path
     * @param mixed  $default
     * @return mixedf
     */
    public function getConfig(string $path=null, $default=null)
    {
        return config($this->getConfigPath($path), $default);
    }

    /**
     * Define the name of the filter.
     *
     * @param  string $name
     * @return self
     */
    protected function setName(string $name)
    {
        $this->needsToBeUnlocked();

        if (!is_null($this->name)) {
            throw new \LogicException('The filter name cannot be defined multiple times');
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Return the type object of the field.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name ?: Str::snake(\class_basename(static::class));
    }

    public function buildParams($params): Collection
    {
        if (! \is_array($params)) {
            $params = ['value' => $params];
        }

        $defaultParams = $this->getDefaultParams();
        $params = new Collection(\array_merge($defaultParams, $params));

        if (! $params->has('value')) {
            throw new FilterException($this, 'Missing value for filter');
        }

        if (count($params) !== count($defaultParams)) {
            $keys = implode(', ', array_diff(array_keys($params->toArray()), array_keys($defaultParams)));

            throw new FilterException($this, 'Some parameters are not allowed for this filter: '.$keys);
        }

        // Follow keys and retrieve at execution the value.
        $keys = $params->keys();

        foreach ($keys as $key) {
            if (\method_exists($this, $method = 'check'.Str::studly($key))) {
                $subValue = \call_user_func([$this, $method], $params->get($key), $params);
            }

            $params->put($key, $subValue);
        }

        return $params;
    }

    protected function locking()
    {

    }

    protected function owned()
    {

    }

    /**
     * Return a property, or set one.
     *
     * @param  string $method
     * @param  array  $args
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        if (static::hasMacro($method)) {
            return $this->callMacro($method, $args);
        }

        return $this->callProperty($method, $args);
    }
}
