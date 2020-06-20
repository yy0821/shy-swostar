<?php
/**
 * 助手函数
 */
if (!function_exists('app'))
{
    /**
     * @param null $app
     * @return mixed
     */
    function app($app = null)
    {
        if (empty($app)){
            return \SwoStar\Foundation\Application::getInstance();
        }
        return \SwoStar\Foundation\Application::getInstance()->make($app);
    }
}

if (!function_exists('dd'))
{
    /**
     * @param $message
     * @param null $description
     */
    function dd($message, $description = null)
    {
       \SwoStar\Console\Input::info($message, $description);
    }
}

if (!function_exists('config'))
{
    /**
     * @param null $key
     * @return mixed
     */
    function config($key = null)
    {
        if (empty($key)){
            return app('config')->items;
        }
        return app('config')->get($key);
    }
}