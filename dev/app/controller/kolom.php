<?php

namespace controller;
require_once __DIR__ . '/../handler.php';

use app\Handler;

class Kolom extends Handler
{
    private static $kolom = [
        'name' => null,
        'typeData' => null,
        'size' => null,
        'enum' => null,
        'comments' => null,
        'relasi' => null,
        'relasi_id' => null,
        'table_id' => null,
    ];
    private static $table = 'kolom';

    function filters($data)
    {
        $ret = array_intersect_key($data, self::$kolom);
        return $this->process($ret, $data['method'], self::$table, false);
    }
    function command($q)
    {
        return $this->query($q);
    }
}
