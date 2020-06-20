<?php
namespace SwoStar\Foundation;

use SwoStar\Config\Config;
use SwoStar\Container\Container;
use SwoStar\Event\Event;
use SwoStar\Message\Http\HttpRequest;
use SwoStar\Routes\Route;
use SwoStar\Server\Http\HttpServer;
use SwoStar\Server\WebSocket\WebSocketServer;

/**
 * 应用类
 * @package SwoStar\Foundation
 */
class Application extends Container
{
    protected const SWOSTAR_WELCOME = "
      _____                     _____     ___
     /  __/             ____   /  __/  __/  /__   ___ __    __  __
     \__ \  | | /| / / / __ \  \__ \  /_   ___/  /  _`  |  |  \/ /
     __/ /  | |/ |/ / / /_/ /  __/ /   /  /_    |  (_|  |  |   _/
    /___/   |__/\__/  \____/  /___/    \___/     \___/\_|  |__|
    ";

    /**
     * 系统根目录
     * @var string
     */
    protected $basePath = '';
    public $type = 'tcp';//tcp,udp,http,ws

    public function __construct($path = null)
    {
        echo self::SWOSTAR_WELCOME."\n";
        if (!empty($path)){
            $this->setBasePath($path);
        }
        $this->registerBinds();
    }

    public function run($argv)
    {
        $server = null;
        if (isset($argv[1])){
            $this->type = $argv[1];
        }
        switch ($this->type){
            case 'tcp':
                break;
            case 'udp':
                break;
            case 'ws':
                $server = new WebSocketServer($this);
                break;
            case 'http':
                $server = new HttpServer($this);
                break;
            default:
                exit('404');
                break;
        }
        $server->start();
    }

    /**
     * 绑定类到IOC容器
     */
    protected function registerBinds()
    {
        self::setInstance($this);
        $binds = [
            'config' => (new Config()),
            'route' => (Route::getInstance()->registerRoute()),
            'event' => $this->registerEvent(),
            'httpRequest' => (new HttpRequest())
        ];
        foreach ($binds as $key => $value)
        {
            $this->bind($key,$value);
        }
    }

    /**
     * 注册事件类
     * @return Event
     */
    protected function registerEvent()
    {
        $event = new Event();
        $files = scandir($this->getBasePath()."/app/Listeners");
        foreach ($files as $key => $file){
            if ($file === '.' || $file === '..'){
                continue;
            }
            if (!is_dir($file)){
                $class = "App\\Listeners\\".explode('.',$file)[0];
                if (class_exists($class)){
                    $listener = new $class($this);
                    $event->register($listener->getName(),[$listener,'handler',]);
                }

            }
        }
        return $event;
    }

    /**
     * 设置系统根目录
     * @param $path
     */
    public function setBasePath($path)
    {
        $this->basePath = $path;
    }

    /**
     * 获取系统根目录
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }
}