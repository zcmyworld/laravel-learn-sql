# 从0开始写laravel-查询构造器

## 需求设计 - 1

1. 允许在配置文件中决定使用哪一个存储系统（MySQL/sqlite/..)
2. 可以直接执行 sql 语句
3. sql 语句中的变量与 sql 语句字符串分离

## 实现

在 application/config 下创建 db.php 作为数据库的配置文件：

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


在 system 下创建 db 目录，创建 connector.php 文件，作为一个连接生成工厂：

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


通过在配置文件中获取的数据库名称，数据库用户密码来建立连接。

在 system 目录下新建文件 db.php 作为数据库操作类，拥有建立连接 connection() 和 执行查询 query() 两个方法：

	<?php

	namespace System;

	class DB
	{
	    private static $connections = array();

	    public static function connection($connection = null)
	    {

	    }

	    public static function query($sql, $bindings = array(), $connection = null)
	    {

	    }
	}


DB 类可以管理多个连接，使用不同的连接可以对不同的数据库进行查询，DB::connection()方法负责建立连接，如果连接已经存在，则返回该连接，而不重新创建：

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

DB::query() 方法实现一个对 PDO 查询的封装：

	public static function query($sql, $bindings = array(), $connection = null)
    {
        $query = static::connection($connection)->prepare($sql);

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


最后，可以通过以下两种方式实现查询：

	$rs = \System\DB::query("select * from user where id = 1");

-

	$rs = \System\DB::query("select * from user where id = ?", array(1));


## 需求设节 - 2

1. 构造查询生成器，使得数据库语句书写更加便捷而不用手写 sql 语句

## API 设计

查询user表内所有数据

	DB::table('user')->get();

查询user表内所有数据特定字段

	DB::table('user')->get('id', 'user');

查询user表内id为1的数据

	DB::table('user')->select("*")->where('id', '=', 1)->get();

插入一条数据

	DB::table('user')->insert(array(
		"id" => 4,
		"name" => "content4"
	));

更新一条数据

	DB::table('user')->where("id", "=", "4")->update(
		array("name"	=> "content44")
	);

## 实现

## select

在 system/db 中创建 query.php, 拥有属性：

	<?php

	namespace System\DB;

	class Query
	{

	    public $select;

	    public $from;

	    public $table;

	    public $where = 'WHERE 1 = 1';

	    public $bindings = array();

	}

Query 类是用于记录查询信息

在 DB 类中加入方法：

	public static function table($table)
	{
		return new DB\Query($table);
	}

因为 sql 语句需要对字段用 `` 进行包裹，所以在 system/db/query.php 增加方法：

	public function wrap($value, $wrap = '"')
    {
        $wrap = '`';
        return implode('.', array_map(
            function($segment) use ($wrap) {
                return ($segment != '*') ? $wrap.$segment.$wrap : $segment;
            }, explode('.', $value))
        );
    }

构造函数用于指定将要操作的表

	public function __construct($table)
    {
        $this->from = 'FROM '.$this->wrap($this->table = $table);
    }

增加 select 方法，用于构造要查询你的字段， 比如  select * 或者  select [字段],[ 字段]

	// 用于构造查询字段
    public function select()
    {
        $this->select = 'SELECT ';

        // 对于 select 的参数，调用 $this->wrap 并且转化为字符串
        $this->select .= implode(', ', array_map(array($this, 'wrap'), func_get_args()));

        return $this;
    }

当有了查询字段和对应的表时，就可以完成最简单的查询语句，形如 select * from [tablename];

再构造一个对 Query 类的编译器，在 system/db 下创建 query 目录， 创建 compiler.php 文件：

	<?php namespace System\DB\Query;

	// sql 语法解析器, 对 Query 对象的属性进行解析转换成为 sql 语句
	class Compiler {
	    public static function select($query)
	    {
	        $sql = $query->select.' '.$query->from.' '.$query->where;
	        return $sql;
	    }
	}

在 system/db/query.php 中增加调用 sql 解析器的方法 get():

	public function get()
    {
        if (is_null($this->select))
        {
            call_user_func_array(array($this, 'select'), (count(func_get_args()) > 0) ? func_get_args() : array('*'));
        }

        return \System\DB::query(Query\Compiler::select($this), $this->bindings);
    }

即可完成两个 API

## insert

查询user表内所有数据

	DB::table('user')->get();

查询user表内所有数据特定字段

	DB::table('user')->get('id', 'user');

要加上条件查询，只需在system/db/query.php 中加上

	public function where($column, $operator, $value, $connector = 'AND')
    {
        $this->where .= ' '.$connector.' '.$this->wrap($column).' '.$operator.' ?';
		$this->bindings[] = $value;

		return $this;
    }

即可完成

	DB::table('user')->select("*")->where('id', '=', 1)->get();

要完成数据库的insert操作，需要以下实现：

在 system/db/query.php 中加上　

	public function parameterize($values)
    {
        return implode(', ', array_fill(0, count($values), '?'));
    }

用于填充拼凑预处理　sql 的　？

在编译器 system/db/query/compiler.php　中加入 inset 的处理：

	public static function insert($query, $values)
    {
        $sql = 'INSERT INTO ' . $query->table . ' (';

        $columns = array();

        foreach (array_keys($values) as $column)
        {
            $columns[] = $query->wrap($column);
        }

        return $sql .= implode(', ', $columns) . ') VALUES (' . $query->parameterize($values) . ')';
    }

在　system/db/query.php 里面加上：

	public function insert($values)
    {
        return \System\DB::query(Query\Compiler::insert($this, $values), array_values($values));
    }

即先根据　Query 对象从编译器获取　sql 语句，在调用　DB::query　执行．

## update

在 system/db/query.php 加上

	public function update($values)
    {
        return \System\DB::query(Query\Compiler::update($this, $values), array_merge(array_values($values), $this->bindings));
    }

在 system/db/query/compiler.php　加上

	public static function update($query, $values)
    {
        $sql = 'UPDATE '.$query->table.' SET ';

        $columns = array();

        foreach (array_keys($values) as $column)
        {
            $columns[] = $query->wrap($column).' = ?';
        }

        return $sql .= implode(', ', $columns).' '.$query->where;
    }


整个框架项目结构形如：

* application
	* |- config
		* |-applicationi.php
		* |-db.php
		* |-error.php
		* |-session.php
	* |- views
		* |-home
			* index.php
	* |- routes.php
* public
	* |- index.php
* system
	* |-db
		* |- query
			* |-compiler.php
		* |- connector.php
		* |- query.php
	* |- session
		* |- driver
			* |- file.php
		* |- driver.php
		* |- factory.php
	* |- config.php
	* |- cookie.php
	* |- db.php
	* |- error.php
	* |- loader.php
	* |- request.php
	* |- response.php
	* |- route.php
	* |- session.php
	* |- str.php
	* |- router.php
	* |- view.php