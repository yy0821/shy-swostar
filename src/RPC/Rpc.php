<?php

namespace SwoStar\RPC;

use Swoole\Server;

/**
 * Class Rpc
 * @package SwoStar\RPC
 */
class Rpc
{
    protected $port_server = null;
    protected $event = [
        "connect"  => "onConnect",
        "receive"  => "onReceive",
        "close"    => "onClose",
        "packet"   => "onPacket",
    ];

    public function __construct(Server $server,$config)
    {
        $this->port_server = $server->listen($config['host'], $config['port'], SWOOLE_SOCK_TCP);
        $this->port_server->set($config['config']);
        $this->setEvent();
    }

    protected function setEvent()
    {
        foreach ($this->event as $event => $func) {
            $this->port_server->on($event, [$this, $func]);
        }
    }

    public function onConnect(Server $server, $fd)
    {
        echo "Client:Connect.\n";
    }

    public function onReceive(Server $server, $fd, $from_id, $data)
    {
        $server->send($fd, 'Swoole: '.$data);
        $server->close($fd);
    }

    public function onClose(Server $server, $fd)
    {
        echo "Client: Close.\n";
    }

    public function onPacket(Server $server, $data, $addr)
    {
        var_dump($data, $addr);
    }
}