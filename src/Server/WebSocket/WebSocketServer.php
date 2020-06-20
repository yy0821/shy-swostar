<?php

namespace SwoStar\Server\WebSocket;

use Swoole\WebSocket\Server;
use SwoStar\Message\Http\HttpRequest;
use SwoStar\Server\Http\HttpServer;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;

/**
 * WebSocketServer
 * @package SwoStar\Server\WebSocket
 */
class WebSocketServer extends HttpServer
{
    protected $isHttp = false;

    protected  function createServer()
    {
        $this->server = new Server($this->host,$this->port);
    }

    protected  function initEvent()
    {
        $event = [
            'request'   => 'onRequest',
            'open'      => 'onOpen',
            'message'   => 'onMessage',
            'close'     => 'onClose',
        ];
        if ($this->app->make('config')->get('server.webSocket.is_handShake')){
            $event['handshake'] = 'onHandshake';
        }
        $this->setEvent('sub',$event);
    }

    protected function initConfig()
    {
        $this->host = $this->app->make('config')->get('server.webSocket.host');
        $this->port = $this->app->make('config')->get('server.webSocket.port');
        $this->config = $this->app->make('config')->get('server.webSocket.config');
    }

    public function onRequest(Request $request, Response $response)
    {
        $this->isHttp = true;

        $uri = $request->server['request_uri'];

        if ($uri == '/favicon.ico') {
            $response->status(404);
            $response->end();
            return null;
        }
        $httpRequest = HttpRequest::init($request);
        $method = $httpRequest->getMethod();
        $request_uri = $httpRequest->getRequestUri();
        $end = $this->app->make('route')->setFlag('Http')->setMethod($method)->match($request_uri);
        $response->end($end);
    }

    public function onHandshake(Request $request, Response $response) {
        $this->app->make('event')->trigger('ws-hand',[$this,$request,$response]);
        $this->onOpen($this->server,$request);
    }

    public function onOpen(Server $server, Request $request)
    {
        Context::init($request->fd,$request);
        $uri = $request->server['path_info'];
        $this->app->make('route')->setFlag('WebSocket')->setMethod('open')->match($uri, [$server, $request]);
    }

    public function onMessage(Server $server, Frame $frame)
    {
        $uri = (Context::get($frame->fd))['uri'];
        $this->app->make('route')->setFlag('WebSocket')->setMethod('message')->match($uri, [$server, $frame]);
        $this->app->make('event')->trigger('ws-message',[$this,$server,$frame]);
    }

    public function onClose(Server $server, $fd) {
        $uri = (Context::get($fd))['uri'];
        $this->app->make('route')->setFlag('WebSocket')->setMethod('close')->match($uri, [$server, $fd]);
        if (!$this->isHttp){
            $this->app->make('event')->trigger('ws-close',[$this,$server,$fd]);
        }
        Context::del($fd);
    }

    public function sendAll($data)
    {
        foreach ($this->server->connections as $k=>$fd){
            if ($this->server->exists($fd)){
                $this->server->push($fd,$data);
            }
        }
    }

    public function push($fd,$data)
    {
        $this->server->push($fd,$data);
    }
}