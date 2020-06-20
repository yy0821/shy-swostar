<?php

namespace SwoStar\Event;

use SwoStar\Foundation\Application;

/**
 * 事件抽象类
 * @package SwoStar\Event
 */
abstract class Listener
{
    protected $name = 'listener';
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public abstract function handler();

    public function getName()
    {
        return $this->name;
    }

    public function getApp()
    {
        return $this->app;
    }
}