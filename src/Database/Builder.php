<?php

namespace SwoStar\Database;

/**
 * 查询构造器
 * @package SwoStar\Database
 */
class Builder
{
    /**
     * @var \PDO
     */
    private $_db = null;                //数据库连接句柄
    private $_table = null;             //表名
    private $_field = '*';              //字段名
    private $_where = null;             //where条件
    private $_order = null;             //order排序
    private $_limit = null;             //limit限定查询
    private $_group = null;             //group分组
    private $fetchSQL = false;          //是否输出sql语句

    public function __construct()
    {
        $this->_db = Pool::getInstance()->getConnection();
        if (!$this->_db) return false;
    }

    /**
     * 设置表名
     * @param $table string 完整表名
     * @return $this
     */
    public function name($table)
    {
        $this->_table = $table;
        return $this;
    }

    /**
     * 设置表名
     * @param $table string 除去前缀的表名
     * @return $this
     */
    public function table($table)
    {
        $table = config('database.mysql.prefix') . $table;
        $this->_table = $table;
        return $this;
    }

    /**
     * @param $sql
     * @return array
     */
    public function query($sql)
    {
        if (!$sql) new \Exception('请输入SQL');

        if ($this->fetchSQL) return $sql;

        $stmt = $this->_db->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $rows;
    }

    /**
     * 设置查询字段
     * @param $str
     * @return $this
     * @throws \Exception
     */
    public function field($str)
    {
        if (empty($str)){
            throw new \Exception('请输入字段名');
        }else{
            $str = explode(',',$str);
            $field = [];
            foreach ($str as $k=>$v){
                $field[] = "`".$v."`";
            }
            $this->_field = implode(',',$field);
        }
        return $this;
    }

    /**
     * 获取表里所有数据
     * @param $table string 完整表名
     * @return array|string
     * @throws \Exception
     */
    public function getAll($table){
        if (!$table) throw new \Exception('请输入表名');
        $sql = "SELECT * FROM {$table}";

        if ($this->fetchSQL) return $sql;

        $stmt = $this->_db->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $rows;
    }

    /**
     * 查询全部记录
     * @return array|string
     */
    public function select()
    {
        if (empty($this->_group)){
            $sql = "SELECT {$this->_field} FROM {$this->_table} {$this->_where} {$this->_order} {$this->_limit}";
        }else{
            $sql = "SELECT {$this->_field} FROM {$this->_table} {$this->_where} {$this->_group} {$this->_order} {$this->_limit}";
        }

        if ($this->fetchSQL) return $sql;

        $stmt = $this->_db->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $rows;
    }

    /**
     * 查询单条记录
     * @param null $id
     * @return mixed|string
     */
    public function find($id = null)
    {
        if (empty($id) && empty($this->_where)) return false;

        if (!empty($this->_where) && !empty($id)){
            return false;
        }elseif(empty($this->_where) && !empty($id)){
            $this->_where .= "WHERE `id` = " . $id;
        }

        if (empty($this->_group)){
            $sql = "SELECT {$this->_field} FROM {$this->_table} {$this->_where} {$this->_order} {$this->_limit}";
        }else{
            $sql = "SELECT {$this->_field} FROM {$this->_table} {$this->_where} {$this->_group} {$this->_order} {$this->_limit}";
        }

        if ($this->fetchSQL) return $sql;

        $stmt = $this->_db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row;
    }

    /**
     * 查询单条记录的字段值
     * @param null $field
     * @return mixed|string
     */
    public function value($field = null)
    {
        if (empty($field) && empty($this->_where)) return false;

        if (empty($this->_group)){
            $sql = "SELECT {$this->_field} FROM {$this->_table} {$this->_where} {$this->_order} {$this->_limit}";
        }else{
            $sql = "SELECT {$this->_field} FROM {$this->_table} {$this->_where} {$this->_group} {$this->_order} {$this->_limit}";
        }

        if ($this->fetchSQL) return $sql;

        $stmt = $this->_db->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row[$field];
    }

    /**
     * 设置where条件
     * @param null $where string|array 条件
     * @return $this
     */
    public function where($where = null)
    {
        if (!empty($this->_where)){
            $this->_where .= " AND ";
        }else{
            $this->_where .= "WHERE ";
        }
        if (is_string($where) && !empty($where)){
            $this->_where .= $where;
        }elseif (is_array($where)){
            foreach ($where as $k=>$v){
                if ($v !== end($where)){
                    $this->_where .= "`{$k}` = " .$v. " AND ";
                }else{
                    $this->_where .= "`{$k}` = " .$v;
                }
            }
        }else{
            return $this;
        }
        return $this;
    }

    /**
     * 设置order条件
     * @param null $order
     * @return $this
     */
    public function order($order = null)
    {
        if (is_string($order) && !empty($order)){
            $this->_order = "ORDER BY " . $order;
        }
        return $this;
    }

    /**
     * 设置group条件
     * @param null $group
     * @return $this
     */
    public function group($group = null)
    {
        if(is_array($group)){
            $this->_group = "GROUP BY ".implode(',',$group);
        }elseif(is_string($group)&&!empty($group)){
            $this->_group = "GROUP BY ".$group;
        }
        return $this;
    }

    /**
     * 设置limit条件
     * @param null $limit
     * @return $this
     */
    public function limit($limit = null)
    {
        if(is_string($limit) || !empty($limit)){
            $this->_limit = "LIMIT " . $limit;
        }elseif(is_numeric($limit)){
            $this->_limit = "LIMIT " . $limit;
        }
        return $this;
    }

    /**
     * 插入单条数据
     * @param $data
     * @param bool $flag false返回影响行数 true返回最后插入ID
     * @return bool|int|string
     */
    public function insert($data,$flag = false)
    {
        if (!$data || !is_array($data)) return false;
        $field = '';
        $value = '';
        foreach ($data as $k=>$v){
            if ($v !== end($data)){
                $field .= "`{$k}`,";
                $value .= "'{$v}',";
            }else{
                $field .= "`{$k}`";
                $value .= "'{$v}'";
            }
        }
        $sql = "INSERT INTO {$this->_table} ({$field}) VALUES ($value)";

        if ($this->fetchSQL) return $sql;

        $count  =  $this->_db->exec($sql);
        if (!$flag){
            return $count;
        }else{
            return $this->_db->lastInsertId();
        }
    }

    /**
     * 更新数据
     * @param null $data
     * @return bool|int|string
     */
    public function update($data = null)
    {
        if (empty($data) || empty($this->_where) || !is_array($data)) return false;
        $update = '';
        foreach ($data as $k=>$v){
            if ($v !== end($data)){
                $update .= "`{$k}` = '{$v}',";
            }else{
                $update .= "`{$k}` = '{$v}'";
            }
        }
        $sql = "UPDATE {$this->_table} SET {$update} {$this->_where}";

        if ($this->fetchSQL) return $sql;

        $count  =  $this->_db->exec($sql);
        return $count;
    }

    /**
     * 删除数据
     * @param null $where
     * @return bool|int|string
     */
    public function delete($where = null)
    {
        if (empty($where) || empty($this->_where)) return false;

        if (!empty($this->_where)){
            $this->_where .= " AND ";
        }else{
            $this->_where .= "WHERE ";
        }

        if (is_numeric($where)){
            $this->_where .= "`id` = {$where}";
        }elseif(is_array($where)){
            foreach ($where as $k=>$v){
                if ($v !== end($data)){
                    $this->_where .= "`{$k}` = '{$v}' AND ";
                }else{
                    $this->_where .= "`{$k}` = '{$v}'";
                }
            }
        }
        $sql = "DELETE FROM {$this->_table} {$this->_where}";

        if ($this->fetchSQL) return $sql;

        $count  =  $this->_db->exec($sql);
        return $count;
    }

    /**
     * 开启事务
     */
    public function begin()
    {
        $this->_db->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        $this->_db->commit();
    }

    /**
     * 回滚事务
     */
    public function rollBack()
    {
        $this->_db->rollBack();
    }

    /**
     * 是否输出sql语句
     */
    public function fetchSQL()
    {
        $this->fetchSQL = true;
        return $this;
    }
}