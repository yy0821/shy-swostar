<?php

namespace SwoStar\Message\Http;

use Swoole\Http\Request;

/**
 * HTTP请求类
 * @package SwoStar\Message\Http
 */
class HttpRequest
{
    protected $swoole_request;
    protected $server;
    protected $method;
    protected $request_uri;

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|mixed
     */
    public static function init(Request $request)
    {
        $self = app('httpRequest');
        $self->swoole_request = $request;
        $self->server = $request->server;
        $self->method = $request->server['request_method'];
        $self->request_uri = $request->server['request_uri'];
        return $self;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getRequestUri()
    {
        return $this->request_uri;
    }
}