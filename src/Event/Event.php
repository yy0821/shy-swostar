<?php

namespace SwoStar\Event;

/**
 * 事件类
 * @package SwoStar\Event
 */
class Event
{
    /**
     * 时间池
     * @var array
     */
    protected $event = [];

    /**
     * 注册事件
     * @param $event
     * @param $callback
     */
    public function register($event,$callback)
    {
        $event = strtolower($event);

        $this->event[$event] = ['callback' => $callback];
    }

    /**
     * 触发事件
     * @param $event
     * @param array $param
     * @return bool|string
     */
    public function trigger($event,$param = [])
    {
        $event = strtolower($event);

        if (isset($this->event[$event])){
            ($this->event[$event]['callback'])(...$param);
            return true;
        }
        return '事件不存在';
    }

    /**
     * 获取对应事件类
     * @param null $event
     * @return array|mixed
     */
    public function getEvent($event = null)
    {
        return empty($event) ? $this->event : $this->event[$event];
    }
}