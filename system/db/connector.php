<?php namespace System\DB;

class Connector {

    /**
     * The PDO connection options.
     *
     * @var array
     */
    public static $options = array(
        \PDO::ATTR_CASE => \PDO::CASE_LOWER, // 强制列名小写
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, // 发生错误的时候，抛出 PDOException
        \PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL, // 在获取数据时不将空字符串转换成 SQL 中的 NULL
        \PDO::ATTR_STRINGIFY_FETCHES => false, // 提出的时候禁止将数值转为字符串
    );

    /**
     * Establish a PDO database connection.
     *
     * @param  object  $config
     * @return PDO
     */
    public static function connect($config)
    {
        // 加入更多的判断条件，可以根据不同的配置获取不同的数据库连接
        if ($config->driver == 'mysql')
        {
            $connection = new \PDO($config->driver.':host='.$config->host.';dbname='.$config->database, $config->username, $config->password, static::$options);

            // 设置编码
            if (isset($config->charset))
            {
                $connection->prepare("SET NAMES '".$config->charset."'")->execute();
            }

            return $connection;
        }
        else
        {
            throw new \Exception('Database driver '.$config->driver.' is not supported.');
        }
    }

}