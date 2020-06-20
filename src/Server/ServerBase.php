<?php
namespace SwoStar\Server;

use Redis;
use Swoole\Server;
use Swoole\Timer;
use SwoStar\Database\Pool;
use SwoStar\RPC\Rpc;
use SwoStar\Supper\Inotify;
use SwoStar\Foundation\Application;

/**
 * ServerBase
 * @package SwoStar\Server
 */
abstract class ServerBase
{
    protected $mod = SWOOLE_PROCESS;
    protected $sock_type = SWOOLE_SOCK_TCP;
    protected $app = null;
    /**
     * @var \Swoole\Server
     */
    protected $server;
    /**@var Redis */
    protected $redis = null;
    protected $port;
    protected $host;
    protected $config;
    protected $inotify = null;
    protected $serverType = 'TCP';
    protected $watchFile = false;
    protected $pidFile = "/runtime/swostar.pid";
    protected $pidMap = [
        'masterPid'  => 0,
        'managerPid' => 0,
        'workerPids' => [],
        'taskPids'   => []
    ];
    protected $event = [
        "server" => [
            "start"        => "onStart",
            "managerStart" => "onManagerStart",
            "managerStop"  => "onManagerStop",
            "shutdown"     => "onShutdown",
            "workerStart"  => "onWorkerStart",
            "workerStop"   => "onWorkerStop",
            "workerError"  => "onWorkerError",
            "workerExit"  => "onWorkerExit",
        ],
        "sub" => [],
        "ext" => []
    ];

    protected abstract function createServer();
    protected abstract function initEvent();
    protected abstract function initConfig();

    public function __construct(Application $app)
    {
        $this->app = $app;
        if ($this->app->getBasePath()){
            $this->watchFile = true;
        }
        $this->initConfig();
        $this->createServer();
        $this->initEvent();
        $this->setSwooleEvent();
    }

    public function start()
    {
        if (empty($this->server)) {
            return "error";
        }
        $this->server->set($this->config);
        if ($this->app->make('config')->get('server.is_rpc')){
            new Rpc($this->server,$this->app->make('config')->get('server.rpc'));
        }
        $this->server->start();
    }

    protected function setSwooleEvent()
    {
        foreach ($this->event as $type => $events) {
            foreach ($events as $event => $func) {
                $this->server->on($event, [$this, $func]);
            }
        }
    }

    public function watchEvent()
    {
        return function ($event){
            $action = 'file:';
            switch ($event['mask']) {
                case IN_CREATE:
                    $action = 'IN_CREATE';
                    break;
                case IN_DELETE:
                    $action = 'IN_DELETE';
                    break;
                case IN_MODIFY:
                    $action = 'IN_MODIF';
                    break;
                case IN_MOVE:
                    $action = 'IN_MOVE';
                    break;
            }
            echo "因为：". $action. "重启服务\n";
            $this->server->reload();
        };
    }

    public function onStart(Server $server)
    {
        $this->pidMap['masterPid'] = $server->master_pid;
        $this->pidMap['managerPid'] = $server->manager_pid;
        if ($this->watchFile){
            $this->inotify = new Inotify($this->app->getBasePath(),$this->watchEvent());
            $this->inotify->start();
        }
        $this->app->make('event')->trigger('start');
    }

    public function onManagerStart(Server $server){}
    public function onManagerStop(Server $server){}
    public function onShutdown(Server $server){}

    public function onWorkerStart(Server $server, int $worker_id)
    {
        $this->pidMap['workerPids'] = [
            'id'  => $worker_id,
            'pid' => $server->worker_pid
        ];
        Pool::getInstance();
        $this->redis = new Redis();
        $this->redis->pconnect($this->app->make('config')->get('database.redis.host'),$this->app->make('config')->get('database.redis.port'));
    }

    public function onWorkerStop(Server $server, int $worker_id){}

    public function onWorkerExit(Server $server, int $worker_id)
    {
        Timer::clearAll();
    }

    public function onWorkerError(Server $server, int $workerId, int $workerPid, int $exitCode, int $signal){}

    public function setWatchFile($watch)
    {
        $this->watchFile = $watch;
        return $this;
    }

    public function getServerType()
    {
        return $this->serverType;
    }

    public function setServerType($serverType)
    {
        $this->serverType = $serverType;
        return $this;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    public function getEvent(): array
    {
        return $this->event;
    }

    public function setEvent($type, $event)
    {
        if ($type == "server") {
            return $this;
        }
        $this->event[$type] = $event;
        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig($config)
    {
        $this->config = array_map($this->config, $config);
        return $this;
    }

    public function getRedis()
    {
        return $this->redis;
    }

    public function getServer()
    {
        return $this->server;
    }
}