<?php

namespace SwoStar\Container;

use Closure;
use Exception;

/**
 * IOC容器类
 * @package SwoStar\Container
 */
class Container
{
    protected static $instance;
    /**
     * 标识=》实例或闭包
     * @var array
     */
    protected $bindings = [];
    protected $instances = [];

    /**
     * 容器绑定的方法
     * @param  string $abstract 标识
     * @param  object $object   实例对象或者闭包
     */
    public function bind($abstract, $object)
    {
        $this->bindings[$abstract] = $object;
    }

    /**
     * 从容器中解析实例对象或者闭包
     * @param string $abstract 标识
     * @param array $parameters 传递的参数
     * @return mixed 是一个闭包或者对象
     * @throws Exception
     */
    public function make($abstract, $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * 从容器中解析实例对象或者闭包
     * @param $abstract
     * @param array $parameters
     * @return mixed
     * @throws Exception
     */
    public function resolve($abstract, $parameters = [])
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        if (!$this->has($abstract)) {
            throw new Exception('没有找到这个容器对象'.$abstract, 500);
        }
        $object = $this->bindings[$abstract];
        if ($object instanceof Closure) {
            return $object();
        }
        return $this->instances[$abstract] = (is_object($object)) ? $object :  new $object(...$parameters) ;
    }

    /**
     * 查看IOC是否有标识对应的实例对象或者闭包
     * @param $abstract
     * @return bool
     */
    public function has($abstract)
    {
        return isset($this->bindings[$abstract]);
    }

    /**
     * 单例模式
     * @return mixed
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * 设置当前单例对象
     * @param null $container
     * @return null
     */
    public static function setInstance($container = null)
    {
        return static::$instance = $container;
    }
}
