<?php

namespace SwoStar\Routes;

/**
 * 路由类
 * @package SwoStar\Routes
 */
class Route
{
    protected static $instance = null;
    protected $flag = null; //http,webSocket
    protected $routes = [];
    protected $method;
    protected $verbs = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
    protected $routeMap = [];

    protected function __construct( )
    {
        $this->routeMap = [
            'Http' => app()->getBasePath().'/route/http.php',
            'WebSocket' => app()->getBasePath().'/route/webSocket.php',
        ];
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance ;
    }

    public function get($uri, $action)
    {
        return $this->addRoute(['GET'], $uri, $action);
    }

    public function post($uri, $action)
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    public function put($uri, $action)
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    public function patch($uri, $action)
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }

    public function delete($uri, $action)
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    public function any($uri, $action)
    {
        return $this->addRoute($this->verbs, $uri, $action);
    }

    /**
     * WebSocket路由
     * @param $uri
     * @param $controller
     * @return $this
     */
    public function wsController($uri, $controller)
    {
        $actions = ['open','message','close'];
        foreach ($actions as $key => $action){
            $this->addRoute([$action], $uri, $controller.'@'.$action);
        }
        return $this;
    }

    /**
     * 注册路由
     * @param $methods
     * @param $uri
     * @param $action
     * @return $this
     */
    protected function addRoute($methods, $uri, $action)
    {
        foreach ($methods as $method ) {
            $this->routes[$this->flag][$method][$uri] = $action;
        }
        return $this;
    }

    /**
     * 解析路由
     * @param $request_uri
     * @param array $param
     * @return mixed|string
     */
    public function match($request_uri,$param = [])
    {
        $action = null;
        foreach ($this->routes[$this->flag][$this->method] as $uri => $value) {
            $uri = ($uri && substr($uri,0,1)!='/') ? "/".$uri : $uri;
            if ($request_uri === $uri) {
                $action = $value;
                break;
            }
        }
        if(!empty($action)){
            if ($action instanceof \Closure){
                return $action(...$param);
            }else{
                $namespace = "App\\".$this->flag."\Controllers\\";
                $controller = $namespace.stristr($action,'@',true);
                $func = substr($action,strripos($action,"@")+1);
                $class = new $controller;
                return $class->{$func}(...$param);
            }
        }
        return "404";
    }

    /**
     * 请求标识
     * @param string $flag Http|WebSocket
     * @return $this
     */
    public function setFlag($flag)
    {
        $this->flag = $flag;
        return $this;
    }

    /**
     * 请求类型
     * @param string $method  GET|POST
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * 注册路由文件
     * @return $this
     */
    public function registerRoute()
    {
        foreach ($this->routeMap as $key => $path) {
            $this->flag = $key;
            require_once $path;
        }
        return $this;
    }

    /**
     * 获取路由
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}
