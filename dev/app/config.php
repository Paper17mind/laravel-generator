<?php

namespace app;

require_once __DIR__ . '/TemplateEngine.php';
require_once __DIR__ . '/ConfigBuilder.php';

use SQLite3;
use app\TemplateEngine;
use app\ConfigBuilder;

class Config
{
    public $db;
    private $engine;

    public function __construct()
    {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);
        $this->db = new SQLite3(__DIR__ . '/../database/databaseProd.db');
        $this->engine = new TemplateEngine();
    }

    public function toUpperName($name)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', (string)$name)));
    }

    private function toPlural($str)
    {
        return $str . 's';
    }

    private function toCamel($str)
    {
        return lcfirst($this->toUpperName($str));
    }

    function createController($data, $projectId, $wizardData = null)
    {
        $project_dir = "../public/$projectId";
        
        $projectQuery = $this->db->query("SELECT * FROM projects WHERE id = $projectId");
        $projectData = $projectQuery->fetchArray(SQLITE3_ASSOC);
        $finalConfigData = array_merge($projectData ?: [], $wizardData ?: []);
        
        $builder = new ConfigBuilder($finalConfigData);
        $config = $builder->build();
        $backendFramework = $builder->getBackendFramework();
        $frontendFramework = $builder->getFrontendFramework();
        $components = $config['backend']['components'] ?? [];

        // Structure folders - Hasil akhir dipisah per folder
        $dirs = [];
        if ($backendFramework === 'laravel') {
            if ($components['controller'] ?? true) $dirs[] = "$project_dir/app/Http/Controllers/Api";
            if ($components['model'] ?? true) $dirs[] = "$project_dir/app/Models";
            if ($components['routes'] ?? true) $dirs[] = "$project_dir/routes";
            if ($components['migration'] ?? true) $dirs[] = "$project_dir/database/migrations";
        } elseif ($backendFramework === 'adonis') {
            $dirs[] = "$project_dir/app/Controllers/Http";
            $dirs[] = "$project_dir/app/Models";
        } elseif ($backendFramework === 'express') {
            $dirs[] = "$project_dir/src/controllers";
            $dirs[] = "$project_dir/src/models";
        } elseif ($backendFramework === 'go' || $backendFramework === 'golang') {
            $dirs[] = "$project_dir/controllers";
            $dirs[] = "$project_dir/models";
            $dirs[] = "$project_dir/services";
            $dirs[] = "$project_dir/routes";
        }

        if ($frontendFramework === 'vue3') $dirs[] = "$project_dir/frontend/vue/src/views";
        if ($frontendFramework === 'react') $dirs[] = "$project_dir/frontend/react/src/components";

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) mkdir($dir, 0777, true);
        }

        $resource = $data->name;
        $context = [
            'resource' => $resource,
            'resourceUpper' => $this->toUpperName($resource),
            'resourcePlural' => $this->toPlural($resource),
            'resourceCamel' => $this->toCamel($resource),
            'columns' => $data->child
        ];

        // Generation with simplified template path (flat backends style)
        if ($backendFramework === 'laravel') {
            if ($components['controller'] ?? true) file_put_contents("$project_dir/app/Http/Controllers/Api/{$context['resourceUpper']}Controller.php", $this->engine->render("backends/laravel/controller.twig", $context));
            if ($components['model'] ?? true) file_put_contents("$project_dir/app/Models/{$context['resourceUpper']}.php", $this->engine->render("backends/laravel/model.twig", $context));
            if ($components['migration'] ?? true) file_put_contents("$project_dir/database/migrations/" . date('Y_m_d_His') . "_create_{$resource}_table.php", $this->engine->render("backends/laravel/migration.twig", $context));
            if ($components['routes'] ?? true) file_put_contents("$project_dir/routes/api.php", $this->engine->render("backends/laravel/routes.twig", $context) . PHP_EOL, FILE_APPEND);
        } elseif ($backendFramework === 'adonis') {
            file_put_contents("$project_dir/app/Controllers/Http/{$context['resourceUpper']}Controller.ts", $this->engine->render("backends/adonis/controller.twig", $context));
            file_put_contents("$project_dir/app/Models/{$context['resourceUpper']}.ts", $this->engine->render("backends/adonis/model.twig", $context));
        } elseif ($backendFramework === 'express') {
            file_put_contents("$project_dir/src/controllers/{$context['resourceUpper']}Controller.js", $this->engine->render("backends/express/controller.twig", $context));
        } elseif ($backendFramework === 'go' || $backendFramework === 'golang') {
            // Template flat (backends/go/controller.twig), hasil akhir ke file sistem dipecah per folder (controllers/)
            file_put_contents("$project_dir/controllers/{$context['resourceUpper']}Controller.go", $this->engine->render("backends/go/controller.twig", $context));
            file_put_contents("$project_dir/models/{$context['resourceUpper']}.go", $this->engine->render("backends/go/model.twig", $context));
            file_put_contents("$project_dir/services/{$context['resourceUpper']}Service.go", $this->engine->render("backends/go/service.twig", $context));
            file_put_contents("$project_dir/routes/{$context['resourceUpper']}Routes.go", $this->engine->render("backends/go/routes.twig", $context));
        }

        if ($frontendFramework === 'vue3') {
            file_put_contents("$project_dir/frontend/vue/src/views/{$context['resourceUpper']}.vue", $this->engine->render("frontends/vue3/component.vue.twig", $context));
        } elseif ($frontendFramework === 'react') {
            file_put_contents("$project_dir/frontend/react/src/components/{$context['resourceUpper']}.jsx", $this->engine->render("frontends/react/component.jsx.twig", $context));
        }

        return "Successfully generated assets for $resource using $backendFramework";
    }

    function getPreview($data, $projectId)
    {
        $projectQuery = $this->db->query("SELECT * FROM projects WHERE id = $projectId");
        $projectData = $projectQuery->fetchArray(SQLITE3_ASSOC);
        $builder = new ConfigBuilder($projectData ?: []);
        $backendFramework = $builder->getBackendFramework();
        $frontendFramework = $builder->getFrontendFramework();

        $resource = $data->name;
        $context = [
            'resource' => $resource,
            'resourceUpper' => $this->toUpperName($resource),
            'resourcePlural' => $this->toPlural($resource),
            'resourceCamel' => $this->toCamel($resource),
            'columns' => $data->child
        ];

        $previews = [];
        if ($backendFramework === 'laravel') {
            $previews[] = ['name' => "Controller.php", 'language' => 'php', 'content' => $this->engine->render("backends/laravel/controller.twig", $context)];
        } elseif ($backendFramework === 'adonis') {
            $previews[] = ['name' => "Controller.ts", 'language' => 'typescript', 'content' => $this->engine->render("backends/adonis/controller.twig", $context)];
        } elseif ($backendFramework === 'express') {
            $previews[] = ['name' => "Controller.js", 'language' => 'javascript', 'content' => $this->engine->render("backends/express/controller.twig", $context)];
        } elseif ($backendFramework === 'go' || $backendFramework === 'golang') {
            $previews[] = ['name' => "controller.go", 'language' => 'go', 'content' => $this->engine->render("backends/go/controller.twig", $context)];
            $previews[] = ['name' => "service.go", 'language' => 'go', 'content' => $this->engine->render("backends/go/service.twig", $context)];
            $previews[] = ['name' => "model.go", 'language' => 'go', 'content' => $this->engine->render("backends/go/model.twig", $context)];
        }

        if ($frontendFramework === 'vue3') {
            $previews[] = ['name' => "Component.vue", 'language' => 'html', 'content' => $this->engine->render("frontends/vue3/component.vue.twig", $context)];
        } elseif ($frontendFramework === 'react') {
            $previews[] = ['name' => "Component.jsx", 'language' => 'javascript', 'content' => $this->engine->render("frontends/react/component.jsx.twig", $context)];
        }

        return json_encode(['data' => $previews]);
    }

    function response($data, $message)
    {
        $response = ['data' => $data, 'message' => $message];
        return json_encode($response);
    }
}
