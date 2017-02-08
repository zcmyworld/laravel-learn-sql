<?php

namespace System;

class DB
{
    private static $connections = array();
    
    public static function connection($connection = null)
    {
        // 判断是否有连接
        if (is_null($connection))     
        {
            $connection = Config::get('db.default');
        }

        // 判断所请求的数据库连接是否为配置中已有的数据库连接
        if (!array_key_exists($connection, static::$connections))
        {
            $config = Config::get('db.connections');
            
            if (!array_key_exists($connection, $config))
            {
                throw new \Exception("Database connection [$connection] is not defined.");
            }

            // 建立连接
            static::$connections[$connection] = DB\Connector::connect((object) $config[$connection]);
        }
        
        return static::$connections[$connection];
    }
    
    public static function query($sql, $bindings = array(), $connection = null)
    {
        $query = static::connection($connection)->prepare($sql);
        var_dump($sql);
        
        $result = $query->execute($bindings);

        if (strpos(strtoupper($sql), 'SELECT') === 0)
        {
            return $query->fetchAll(\PDO::FETCH_CLASS, 'stdClass');
        }
        elseif (strpos(strtoupper($sql), 'UPDATE') === 0 or strpos(strtoupper($sql), 'DELETE') == 0)
        {
            return $query->rowCount(); 
        }
        else
        {
            return $result;
        }
        
    }
    
    public static function table($table)
    {
        return new DB\Query($table);
    }
}

