<?php

namespace controller;
require_once __DIR__ . '/../handler.php';

use app\Handler;

class Table extends Handler
{
    private static $kolom = [
        'name' => null,
    ];
    private static $table = 'tables';

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
