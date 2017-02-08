<?php

return array(

    'default' => 'mysql',

    'connections' => array(
        // 还可以配置其他驱动，比如sqlite
        'mysql' => array(
            'driver'   => 'mysql',
            'host'     => 'localhost',
            'database' => 'laravel',
            'username' => 'root',
            'password' => 'root',
            'charset'  => 'utf8',
        ),

    ),

);