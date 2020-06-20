<?php

namespace SwoStar\Database;

use Swoole\Coroutine\Channel;

/**
 * PDO连接池
 * @package SwoStar\Database
 */
class Pool
{
    protected $host;      //数据库主机名
    protected $dbName;    //使用的数据库
    protected $username;      //数据库连接用户名
    protected $password;      //对应的密码
    protected $charset;      //对应的密码
    protected $dsn;

    protected static $instance = null;
    protected $maxConnection = 10;
    protected $minConnection = 5;
    /**
     * @var Channel;
     */
    protected $channel;
    protected $timeout = 3;
    protected $count;

    protected $idleTime = 10;

    public function __construct()
    {
        $this->init();
        $this->detect();
    }

    /**
     * 初始化
     */
    protected function init()
    {
        $this->channel = new Channel($this->maxConnection);

        $this->host = config('database.mysql.host')?:'127.0.0.1';
        $this->dbName = config('database.mysql.dbName');
        $this->username = config('database.mysql.username');
        $this->password = config('database.mysql.password');
        $this->charset = config('database.mysql.charset')?:'utf8';
        $this->dsn = "mysql:host=$this->host;dbname=$this->dbName";

        for ($i=0; $i<$this->minConnection; $i++) {
            $this->count++;
            $connection = $this->createConnection();
            $this->channel->push($connection);
        }
    }

    /**
     * 创建连接
     */
    protected function createConnection()
    {
        try{
            $db = new \PDO($this->dsn,$this->username,$this->password);
            $db->query("set names ".$this->charset);
            return [
                'last_time' => time(),
                'db' => $db
            ];
        }catch (Exception $exception){
            $this->count--;
            return false;
        }
    }

    /**
     * 调用方法
     * @param $conn
     * @param $method
     * @param $sql
     * @return string
     */
    public function call($conn, $method, $sql)
    {
        try{
            return $conn->{$method}($sql);
        } catch (Exception $exception){
            return $exception->getMessage();
        }
    }

    /**
     * 获取连接
     * @return PDO
     */
    public function getConnection()
    {
        if ($this->channel->isEmpty()){
            if ($this->count < $this->maxConnection){
                $this->count++;
                $connection =  $this->createConnection();
                $this->channel->push($connection);
            }else{
                echo '请等待';
            }
        }
        return ($this->channel->pop($this->timeout))['db'];
    }

    /**
     * 释放连接
     * @param $conn
     */
    public function freeConnection($conn)
    {
        $connection = [
            'last_time' =>time(),
            'db'=> $conn
        ];
        $this->channel->push($connection);
    }

    /**
     * 定时检查、关闭不活动的连接
     */
    protected function detect()
    {
        swoole_timer_tick(3000,function (){
            $connections = [];
            while (true){
                if (!$this->channel->isEmpty() && $this->count > $this->minConnection){
                    $connection = $this->channel->pop();
                    if (empty($connection)) continue;
                    if ((time() - $connection['last_time']) > $this->idleTime){
                        $this->count--;
                        $connection['db'] = null;
                    }else{
                        array_push($connections,$connection);
                    }
                }else{
                    break;
                }
            }
            foreach ($connections as $k => $connection){
                $this->channel->push($connection);
            }
        });
    }

    /**
     * 单例模式
     * @return Pool|null
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)){
            self::$instance = new static();
        }
        return self::$instance;
    }
}