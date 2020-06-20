<?php

namespace SwoStar\Database;

/**
 * DB类
 * @package SwoStar\Database
 *
 * @method  static Builder query(string $sql)
 * @method  static Builder name(string $name)
 * @method  static Builder table(string $table)
 * @method  static array getAll(string $table)
 * @method  static void begin()
 * @method  static void commit()
 * @method  static void rollBack(int $toLevel = null)
 */
class DB
{
    private static $passthru = [
        'query',
        'name',
        'table',
        'getAll',
        'begin',
        'commit',
        'rollBack',
    ];

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (!in_array($name, self::$passthru)) {
            throw new \Exception('Method(%s) is not exist!', $name);
        }
        $connection = new Builder();
        return $connection->$name(...$arguments);
    }
}