<?php

/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 01/05/15
 * Time: 16:23.
 */
namespace LeeMason\Filter;

use Closure;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Dispatcher
{
    /**
     * Unsorted filters array.
     *
     * @var array
     */
    protected $filters = [];

    /**
     * Sorted filters array.
     *
     * @var array
     */
    protected $sorted = [];

    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Add a filter.
     *
     * @param string  $name     filter name
     * @param Closure $func     closure function to be applied
     * @param int     $priority priority used for sorting
     * @param string  $ref      reference allows us to remove a specific filter
     */
    public function add($name, $func, $priority = 100, $ref = null)
    {
        if (is_array($name)) {
            foreach ($name as $reference) {
                $this->add($reference, $func, $priority, $ref);
            }
            return;
        }

        if (!isset($this->filters[$name])) {
            $this->filters[$name] = [];
        }

        if (!$func instanceof Closure && $ref === null) {
            $ref = $func;
        }

        $payload = [
            'ref'         => $ref ?: '',
            'function'   => $func,
            'priority'   => $priority,
        ];

        $this->filters[$name][] = $payload;
        unset($this->sorted[$name]);
    }

    /**
     * Apply a filter to something.
     *
     * @param string $name  filter name
     * @param mixed  $value an item we want to apply the filter to
     *
     * @return mixed
     */
    public function apply($name, $value)
    {
        if (!isset($this->filters[$name])) {
            return $value;
        }

        foreach ($this->getFilters($name) as $filter) {
            if ($filter['function'] instanceof Closure) {
                $value = $filter['function']($value);
            } else {
                list($class, $method) = explode('@', $filter['function']);
                if (!method_exists($instance = $this->container->make($class), $method)) {
                    throw new NotFoundHttpException();
                }
                $value = call_user_func([$instance, $method], $value);
            }
        }

        return $value;
    }

    /**
     * Get all of the filters by priority.
     *
     * @param string $name
     *
     * @return array
     */
    public function getFilters($name)
    {
        if (!isset($this->sorted[$name])) {
            $this->sortFilters($name);
        }

        return $this->sorted[$name];
    }

    /**
     * Remove filter by reference.
     *
     * @param string $name filter name
     * @param string $ref  filter reference id
     */
    public function remove($name, $ref = null)
    {
        foreach ($this->filters[$name] as $i => $ary) {
            if ($ary['ref'] == $ref) {
                unset($this->filters[$name][$i]);
            }
        }

        unset($this->sorted[$name]);

        return $this;
    }

    /**
     * Clear all filters by name.
     *
     * @param string $name filter name
     */
    public function clear($name)
    {
        unset($this->filters[$name]);
        unset($this->sorted[$name]);

        return $this;
    }

    /**
     * Handles dynamic apply calls.
     *
     * @param string $method     filter name
     * @param array  $parameters parameters get passed to apply
     *
     * @return mixed
     */
    public function __call($method, $parameters = [])
    {
        if (isset($this->filters[$method])) {
            return $this->apply($method, $parameters[0]);
        }

        return $parameters[0];
    }

    /**
     * Sort the filters array by priority.
     *
     * @param string $name
     */
    protected function sortFilters($name)
    {
        $this->sorted[$name] = [];

        if (isset($this->filters[$name])) {
            uasort($this->filters[$name], [$this, 'sortHandler']);

            $this->sorted[$name] = $this->filters[$name];
        }
    }

    /**
     * Sort handler method used by: uasort.
     *
     * @param int $a compare a
     * @param int $b compare b
     *
     * @return int
     */
    private function sortHandler($a, $b)
    {
        if ($a['priority'] == $b['priority']) {
            return 0;
        }

        return ($a['priority'] < $b['priority']) ? -1 : 1;
    }
}
