<?php namespace System\DB\Query;

// sql 语法解析器, 对 Query 对象的属性进行解析转换成为 sql 语句
class Compiler {

    public static function select($query)
    {
        $sql = $query->select.' '.$query->from.' '.$query->where;

        return $sql;
    }

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
}