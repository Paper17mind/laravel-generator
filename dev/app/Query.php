<?php

namespace app;
require __DIR__ . '/config.php';
use app\Config;
use SQLite3;

class Query extends Config
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
    function generate($id, $wizardData = null)
    {
        $arr = $this->join(
            'tables',
            'kolom',
            'table_id',
            'id',
            'project_id',
            $id
        );
        $decoded = json_decode($arr);
        $this->deleteDirectory("../public/$id");
        if (is_array($decoded) || is_object($decoded)) {
            foreach ($decoded as $val) {
                $this->createController($val, $id, $wizardData);
            }
        }
        return "Success generating project $id";
    }
    function generateCols($id, $wizardData = null)
    {
        $arr = $this->join('tables', 'kolom', 'table_id', 'id', 'id', $id);
        $decoded = json_decode($arr);
        if (is_array($decoded) || is_object($decoded)) {
            foreach ($decoded as $val) {
                $this->createController($val, $id, $wizardData);
            }
        }
        return "Success generating table $id";
    }
    function join($table, $ch, $dest, $params, $where = null, $idParam = null)
    {
        $arr = [];
        $res = [];
        if ($idParam != null) {
            $val = is_numeric($idParam) ? $idParam : "'$idParam'";
            $ret = $this->db->query(
                "select * from $table where $where = $val"
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

    public function deleteDirectory($dir)
    {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
        }
        return rmdir($dir);
    }
}
