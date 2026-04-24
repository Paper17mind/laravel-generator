<?php

use app\Query;
use app\Handler;
use controller\Kolom;
use controller\Table;
use route\Router;
// Load .env file
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:*' /*,"POST,GET,OPTIONS, PUT, DELETE"*/);
header('Access-Control-Allow-Headers:*');
// header('Method:POST');
require_once __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/Query.php';
require __DIR__ . '/app/handler.php';
require __DIR__ . '/app/controller/kolom.php';
require __DIR__ . '/app/controller/table.php';
require __DIR__ . '/Route.php';

define('root', str_replace('/var/www/html', null, __DIR__));
define('request', str_replace(root, null, $_SERVER['REQUEST_URI']));



$q = new Query();
$r = new Router();
$col = new Kolom();
$tab = new Table();
$h = new Handler();

header('Content-Type:application/json');

if (isset($_GET['method'])) {
    if ($_GET['table'] === 'kolom') {
        echo $col->filters($_GET);
    } else {
        echo $tab->filters($_GET);
    }
} elseif (isset($_GET['query'])) {
    echo $h->query($_GET['query']);
} elseif (isset($_GET['exec'])) {
    echo $h->execute($_GET['exec']);
} elseif (isset($_GET['preview'])) {
    $data = json_decode(file_get_contents('php://input'));
    echo $h->preview($data, $_GET['project_id']);
} elseif (isset($_GET['import'])) {
    $data = json_decode(file_get_contents('php://input'));
    echo $h->importData($data);
} elseif (isset($_GET['generate'])) {
    if (isset($_GET['type'])) {
        $wizardData = json_decode(file_get_contents('php://input'), true);
        echo $_GET['type'] === 'project'
            ? $h->create($_GET['generate'], $wizardData)
            : $h->createCols($_GET['generate'], $wizardData);
    }
} elseif (isset($_GET['chat'])) {
    $data = json_decode(file_get_contents('php://input'));
    $framework = $data->framework ?? 'Laravel';
    $history = $data->history ?? [];
    echo $h->askAI($data->prompt, $framework, $history);
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
    if (array_key_exists('Authorization', getallheaders())) {
        return 'OK';
    } else {
        $code = http_response_code(401);
        header('Content-Type:application/json');
        echo json_encode(['code' => 401, 'status' => 'Unauthorized']);
    }
}
