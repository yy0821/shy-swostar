<?php

namespace SwoStar\Config;

/**
 * 加载配置类
 * @package SwoStar\Config
 */
class Config
{
    public $configPath;
    public $items;

    public function __construct()
    {
        $this->configPath = app()->getBasePath()."/config";
        $this->items = $this->loadPHP($this->configPath);
    }

    /**
     * 加载配置文件
     * @param string $path 加载的配置文件目录
     * @return null
     */
    public function loadPHP($path)
    {
        $files = scandir($path);
        $data = null;
        foreach ($files as $key => $file){
            if ($file === '.' || $file === '..'){
                continue;
            }
            if (is_dir($file)){
                $this->loadPHP($file);
            }else{
                $filename = stristr($file,'.php',true);
                $data[$filename] = include $this->configPath."/".$file;
            }
        }
        return $data;
    }

    /**
     * 获取配置
     * @param string $keys （key.key）
     * @return mixed|null
     */
    public function get($keys)
    {
        $data = $this->items;
        foreach (explode('.', $keys) as $key => $value) {
            $data = $data[$value];
        }
        return $data;
    }
}