<?php

namespace SwoStar\Server\Http;

use Swoole\Http\Server;
use SwoStar\Message\Http\HttpRequest;
use SwoStar\Server\ServerBase;
use Swoole\Http\Request;
use Swoole\Http\Response;

/**
 * HttpServer
 * @package SwoStar\Server\Http
 */
class HttpServer extends ServerBase
{
    protected  function createServer()
    {
        $this->server = new Server($this->host,$this->port);
    }

    protected  function initEvent()
    {
        $this->setEvent('sub',[
            'request' => 'onRequest',
        ]);
    }

    protected function initConfig()
    {
        $this->host = $this->app->make('config')->get('server.http.host');
        $this->port = $this->app->make('config')->get('server.http.port');
        $this->config = $this->app->make('config')->get('server.http.config');
    }

    public function onRequest(Request $request, Response $response)
    {
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
}