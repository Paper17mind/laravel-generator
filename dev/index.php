<?php
#&
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:*' /*,"POST,GET,OPTIONS, PUT, DELETE"*/);
header('Access-Control-Allow-Headers:*');
// header('Method:POST');
require __DIR__ . '/app/Query.php';
require __DIR__ . '/app/handler.php';
require __DIR__ . '/app/controller/kolom.php';
require __DIR__ . '/app/controller/table.php';
require __DIR__ . '/Route.php';

define('root', str_replace('/var/www/html', null, __DIR__));
define('request', str_replace(root, null, $_SERVER['REQUEST_URI']));

use app\DB;
use app\Handler;
use controller\Kolom;
use controller\Table;
use route\Router;

$q = new DB();
$r = new Router();
$col = new Kolom();
$tab = new Table();
$h = new Handler();
$arr = [
    'name' => 'name',
    'typeData' => 'typeData',
    'size' => 'size',
    'enum' => 'enum',
    'comments' => 'comments',
    'relasi' => 'relasi',
    'relasi_id' => 'relasi_id',
    'id' => 3,
    'table_id' => 1,
];
$arr2 = [
    'name' => 'asf',
    'typeData' => '2q',
    'size' => 'ttyy',
    'enum' => 'qwrq',
];
// $ret = array_intersect_key($arr2, $arr);
// echo json_encode($ret);
header('Content-Type:application/json');

if (isset($_GET['method'])) {
    if ($_GET['table'] === 'kolom') {
        echo $col->filters($_GET);
    } else {
        echo $tab->filters($_GET);
    }
} elseif (isset($_GET['query'])) {
    if (Authorize() === 'OK') {
        echo $h->query($_GET['query']);
    }
} elseif (isset($_GET['exec'])) {
    // if (Authorize() === 'OK') {
    echo $h->execute($_GET['exec']);
    // }
} elseif (isset($_GET['generate'])) {
    if (isset($_GET['type'])) {
        echo $_GET['type'] === 'project'
            ? $h->create($_GET['generate'])
            : $h->createCols($_GET['generate']);
    }
} elseif (isset($_GET['view'])) {
    $parent = $_GET['parent'];
    $childs = $_GET['child'];
    $key = $_GET['key'];
    echo array_key_exists('param', $_GET)
        ? $h->process(
            [],
            'join',
            $parent,
            'id',
            $childs,
            $key,
            $_GET['where'],
            $_GET['param']
        )
        : $h->process([], 'join', $parent, 'id', $childs, $key);
} else {
    echo $h->process([], 'join', 'tables', 'id', 'kolom', 'table_id');
}

function Authorize()
{
    /*Authorization, this use when user want to run sql directly from frontend*/
    if (array_key_exists('Authorization', getallheaders())) {
        return 'OK';
    } else {
        $code = http_response_code(401);
        header('Content-Type:application/json');
        echo json_encode(['code' => 401, 'status' => 'Unauthorized']);
    }
}
// echo implode(array_keys(['name' => 'sada'])) === 'name' ? 'true'  =>  'false';
// $r->add('/kolom', '2321');
// print_r($r->list());
// echo in_array(request, $route);
// include 'views/forms.php';
// print_r($_GET);
// print_r($_POST);
// print_r($data);
// echo json_encode($_REQUEST);
