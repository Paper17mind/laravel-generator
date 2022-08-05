<?php

namespace app;
require __DIR__ . '/config.php';
use app\Config;
use SQLite3;

class DB extends Config
{
    function cmd($q)
    {
        $arr = [];
        $ret = $this->db->query($q);
        if (!$ret) {
            return $this->db->lastErrorMsg();
        } else {
            while ($table = $ret->fetchArray(SQLITE3_ASSOC)) {
                array_push($arr, $table);
            }
            return $this->response($arr, "'Sukses'");
        }
    }
    function runQuery($cmd)
    {
        $ret = $this->db->exec($cmd);
        if (!$ret) {
            return $this->response($cmd, $this->db->lastErrorMsg());
        } else {
            return $this->response($cmd, 'query berhasil dijalankan');
        }
    }
    public function get($table)
    {
        $arr = [];
        $ret = $this->db->query("select * from $table");
        if (!$ret) {
            return $this->db->lastErrorMsg();
        } else {
            while ($table = $ret->fetchArray(SQLITE3_ASSOC)) {
                array_push($arr, $table);
            }
            return $this->response($arr, "'Sukses'");
        }
    }
    public function insert($table, $cols, $data)
    {
        $ret = $this->db->exec("insert into $table ($cols) values ($data)");
        if (!$ret) {
            return $this->response('error', $this->db->lastErrorMsg());
        } else {
            return $this->response([], 'OK');
        }
    }

    function update($table, $id, $cols, $data)
    {
        $ret = $this->db->exec(
            "update $table set ($cols) = ($data) where id = $id"
        );
        if (!$ret) {
            return $this->response('error', $this->db->lastErrorMsg());
        } else {
            return $this->response([], 'OK');
        }
    }
    function destroy($table, $id)
    {
        $ret = $this->db->exec("delete from $table where id = $id");
        if (!$ret) {
            return $this->response('error', $this->db->lastErrorMsg());
        } else {
            $response = [
                'data' => json_decode($this->get($table)),
                'message' => 'Berhasil disimpan',
            ];
            return $this->response([], 'OK');
        }
    }
    function getOne($table)
    {
        $ret = $this->db->query("select * from $table");
        if (!$ret) {
            return $this->db->lastErrorMsg();
        } else {
            return $ret->fetchArray(SQLITE3_ASSOC);
        }
    }

    // generate
    function generate($id)
    {
        $arr = $this->join(
            'tables',
            'kolom',
            'table_id',
            'id',
            'project_id',
            $id
        );
        $ret = [];
        foreach (json_decode($arr) as $val) {
            $this->createController($val, $id);
        }
        // return 'processing';
    }
    function generateCols($id)
    {
        $arr = $this->join('tables', 'kolom', 'table_id', 'id', 'id', $id);
        $ret = [];
        foreach (json_decode($arr) as $val) {
            $this->createController($val, $id);
        }
        // return 'processing';
    }
    function join($table, $ch, $dest, $params, $where = null, $idParam = null)
    {
        $arr = [];
        $res = [];
        if ($idParam != null) {
            $ret = $this->db->query(
                "select * from $table where $where = $idParam"
            );
        } else {
            $ret = $this->db->query("select * from $table");
        }
        if (!$ret) {
            return $this->db->lastErrorMsg();
        } else {
            while ($table = $ret->fetchArray(SQLITE3_ASSOC)) {
                array_push($res, $table);
            }
        }
        foreach ($res as $r) {
            $id = $r[$params];
            $childs = [];
            $fr = $this->db->query("select * from $ch where $dest = $id");
            while ($table = $fr->fetchArray(SQLITE3_ASSOC)) {
                array_push($childs, $table);
            }
            $arr[] = [
                'id' => $r['id'],
                'name' => $r['name'],
                'child' => $childs,
            ];
        }
        return json_encode($arr);
    }
}
