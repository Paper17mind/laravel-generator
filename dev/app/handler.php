<?php

namespace app;
require_once __DIR__ . '/Query.php';
use app\DB;

class Handler extends DB
{
    public function process(
        $data,
        $method,
        $table,
        $id = 'id',
        $child = null,
        $foreign = null,
        $where = null,
        $idParam = null
    ) {
        $input = $data;
        $fill = array_map(function ($x) {
            return is_integer($x) ? $x : "'$x'";
        }, array_values($input));

        $columns = "'" . implode("' ,'", array_keys($input)) . "'";
        $values = implode(' ,', array_values($fill));

        if ($method === 'post') {
            return $this->insert($table, $columns, $values);
        } elseif ($method === 'update') {
            return $this->update($table, $data['id'], $columns, $values);
        } elseif ($method === 'delete') {
            return $this->destroy($table, $data['id']);
        } elseif ($method === 'join') {
            return $this->join($table, $child, $foreign, $id, $where, $idParam);
        } else {
            return $this->get($table);
        }
    }
    // manual
    function query($q)
    {
        return $this->cmd($q);
    }
    function execute($q)
    {
        return $this->runQuery($q);
    }
    function create($id)
    {
        return $this->generate($id);
    }
    function createCols($id)
    {
        return $this->generateCols($id);
    }
}
// echo $db->get('tables');
// echo $db->insert('kolom', $columns, $values);
// echo $db->update('kolom', 0, $columns, $values);
// echo $db->destroy('kolom', 0);
// echo $db->join('tables', 'kolom', 'tables.id', 'table_id');
