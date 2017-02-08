<?php

namespace System\DB;

class Query
{
    public $select;

    public $from;

    public $table;

    public $where = 'WHERE 1 = 1';

    public $bindings = array();

    public function __construct($table)
    {
        $this->from = 'FROM '.$this->wrap($this->table = $table);
    }
    
    public function where($column, $operator, $value, $connector = 'AND')
    {
        $this->where .= ' '.$connector.' '.$this->wrap($column).' '.$operator.' ?';
		$this->bindings[] = $value;
        
		return $this;
    }

    // 用于构造查询字段
    public function select()
    {
        $this->select = 'SELECT ';

        // 对于 select 的参数，调用 $this->wrap 并且转化为字符串
        $this->select .= implode(', ', array_map(array($this, 'wrap'), func_get_args()));

        return $this;
    }

    public function parameterize($values)
    {
        return implode(', ', array_fill(0, count($values), '?'));
    }
    
    public function insert($values)
    {
        return \System\DB::query(Query\Compiler::insert($this, $values), array_values($values));
    }

    public function update($values)
    {
        return \System\DB::query(Query\Compiler::update($this, $values), array_merge(array_values($values), $this->bindings));
    }

    public function get()
    {
        if (is_null($this->select))
        {
            call_user_func_array(array($this, 'select'), (count(func_get_args()) > 0) ? func_get_args() : array('*'));
        }

        return \System\DB::query(Query\Compiler::select($this), $this->bindings);
    }

    public function wrap($value, $wrap = '"')
    {
        $wrap = '`';
        return implode('.', array_map(
            function($segment) use ($wrap) {
                return ($segment != '*') ? $wrap.$segment.$wrap : $segment;
            }, explode('.', $value))
        );
    }
}
