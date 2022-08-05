<?php

namespace app;
require_once __DIR__ . '/directory.php';
use SQLite3;
use app\Directory;

class Config extends Directory
{
    public $db = [];
    public function __construct()
    {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);
        $this->db = new SQLite3(__DIR__ . '/../database/database.db');
    }
    function response($data, $message)
    {
        $response = [
            'data' => $data,
            'message' => $message,
        ];
        return json_encode($response);
    }
    public function toUpperName($name)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }
    function createController($data, $id)
    {
        #check directory
        if (!is_dir("../public/$id/app/Http/controllers/Api")) {
            mkdir("../public/$id/app/Http/controllers/Api", 0777, true);
        }
        if (!is_dir("../public/$id/app/Models")) {
            mkdir("../public/$id/app/Models", 0777, true);
        }
        if (!is_dir("../public/$id/routes")) {
            mkdir("../public/$id/routes", 0777, true);
        }
        if (!is_dir("../public/$id/view")) {
            mkdir("../public/$id/view", 0777, true);
        }
        if (!is_dir("../public/$id/view/router")) {
            mkdir("../public/$id/view/router", 0777, true);
        }
        if (!is_dir("../public/$id/database/migrations")) {
            mkdir("../public/$id/database/migrations", 0777, true);
        }
        if (!is_dir("../public/$id/graphql")) {
            mkdir("../public/$id/graphql", 0777, true);
        }

        #return from model
        $ret_c = $this->controller($data->name, $data->child);
        $ret_m = $this->model($data->name, $data->child);
        $ret_r = $this->routes($data->name);
        $ret_rf = $this->routesFront($data->name);
        $ret_f = $this->frontend($data->name, $data->child);
        $ret_d = $this->migrations($data->name, $data->child);
        $ret_gql = $this->schemeTypes($data->name, $data->child);
        $ret_gqlQuery = $this->schemeQuery($data->name, $data->child);
        $ret_gqlInput = $this->schemeMutation($data->name, $data->child)[
            'input'
        ];
        $ret_gqlMutation = $this->schemeMutation($data->name, $data->child)[
            'mutation'
        ];

        $name_c = $this->toUpperName($data->name) . 'Controller';
        $name_m = $this->toUpperName($data->name) . 'Model';
        $name_f = $data->name;
        $name_d =
            date('y_m_d') .
            "_000{$data->id}" .
            "_create_{$data->name}_table.php";

        #list dorectory
        $dir_c = "../public/$id/app/Http/controllers/Api/$name_c.php";
        $dir_m = "../public/$id/app/Models/$name_m.php";
        $dir_r = "../public/$id/routes/api.php";
        $dir_rf = "../public/$id/view/router/index.js";
        $dir_f = "../public/$id/view/$name_f.vue";
        $dir_d = "../public/$id/database/migrations/$name_d";
        $dir_gql = "../public/$id/graphql/types.graphql";
        $dir_gqlQuery = "../public/$id/graphql/queries.graphql";
        $dir_gqlInput = "../public/$id/graphql/inputs.graphql";
        $dir_gqlMutation = "../public/$id/graphql/mutations.graphql";
        $dir_sequel = "../public/$id/sequel";
        if (!\is_dir($dir_sequel)) {
            \mkdir($dir_sequel);
            \mkdir($dir_sequel . '/models');
            \mkdir($dir_sequel . '/migrations');
        }
        $f_c = fopen($dir_c, 'w+');
        fputs($f_c, $ret_c);
        // chown($dir_c, 'muhammad');

        $f_m = fopen($dir_m, 'w+');
        fputs($f_m, $ret_m);
        // chown($dir_m, 'muhammad');

        $f_r = fopen($dir_r, 'a');
        fputs($f_r, $ret_r);
        //
        $f_rf = fopen($dir_rf, 'a');
        fputs($f_rf, $ret_rf);

        $f_f = fopen($dir_f, 'w+');
        fputs($f_f, $ret_f);

        $f_d = fopen($dir_d, 'w+');
        fputs($f_d, $ret_d);
        // graphql
        $f_q = fopen($dir_gql, 'a');
        \fputs($f_q, $ret_gql);
        $f_q = fopen($dir_gqlQuery, 'a');
        \fputs($f_q, $ret_gqlQuery);
        $f_q = fopen($dir_gqlInput, 'a');
        \fputs($f_q, $ret_gqlInput);
        $f_q = fopen($dir_gqlMutation, 'a');
        \fputs($f_q, $ret_gqlMutation);
        /**sequelize */
        fputs(fopen("$dir_sequel/sql.js", 'w+'), Sequelize::init('sqldata'));
        $model = Sequelize::models(
            $data->child,
            $this->toUpperName($data->name),
            $data->name
        );
        $date = date('YmdHis');
        fputs(
            fopen(
                "$dir_sequel/models/{$this->toUpperName($data->name)}.js",
                'w+'
            ),
            $model->model
        );
        fputs(
            fopen("$dir_sequel/migrations/$date-create-$data->name.js", 'w+'),
            $model->migration
        );
    }
}
