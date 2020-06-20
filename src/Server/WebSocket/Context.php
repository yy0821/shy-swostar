<?php

namespace SwoStar\Server\WebSocket;

/**
 * 上下文
 * @package SwoStar\Server\WebSocket
 */
class Context
{
    protected static $context = [];

    public static function init($fd,$request)
    {
        self::$context[$fd]['uri'] = $request->server['path_info'];
        self::$context[$fd]['request'] = $request;
    }

    public static function get($fd)
    {
        return self::$context[$fd];
    }

    public static function del($fd)
    {
        unset(self::$context[$fd]);
    }
}