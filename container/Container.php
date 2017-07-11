<?php

class Container
{
    protected $binds;
    protected $instances;

    /**
     * @param $abstract
     * @param $concrete
     */
    public function bind($abstract, $concrete)
    {
        if ($concrete instanceof Closure) {
            $this->binds[$abstract] = $concrete;
        } else {
            $this->instances[$abstract] = $concrete;
        }
    }

    /**
     * @param $abstract
     * @param array $parameters
     * @return mixed
     */
    public function get($abstract, $parameters = [])
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        } else {
            array_unshift($parameters, $this);
            return call_user_func_array($this->binds[$abstract], $parameters);
        }
    }
}
