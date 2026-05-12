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
        if (!is_dir($project_dir)) mkdir($project_dir, 0777, true);

        $projectQuery = $this->db->query("SELECT * FROM projects WHERE id = $projectId");
        $projectData = $projectQuery->fetchArray(SQLITE3_ASSOC);
        $finalConfigData = array_merge($projectData ?: [], $wizardData ?: []);
        
        $builder = new ConfigBuilder($finalConfigData);
        $config = $builder->build();
        $backendFramework = $builder->getBackendFramework();
        $frontendFramework = $builder->getFrontendFramework();
        
        // Fetch ALL tables for this project to build navigation/routes
        $tables = [];
        $tablesQuery = $this->db->query("SELECT * FROM tables WHERE project_id = $projectId");
        while($t = $tablesQuery->fetchArray(SQLITE3_ASSOC)) {
            // Get columns for each table to enrich the context if needed
            $tables[] = $t;
        }

        $projectContext = array_merge($finalConfigData, [
            'project_name' => $projectData['name'] ?? 'codegen_project',
            'backend_framework' => $backendFramework,
            'frontend_framework' => $frontendFramework,
            'tables' => $tables
        ]);

        // 1. Ensure Base Project Files (Docker, README, App Entry, etc.)
        $this->ensureBaseProjectFiles($project_dir, $projectContext);

        // 2. Resource Specific Context (The current table being generated)
        $resource = $data->name;
        $context = array_merge($projectContext, [
            'resource' => $resource,
            'resourceUpper' => $this->toUpperName($resource),
            'resourcePlural' => $this->toPlural($resource),
            'resourceCamel' => $this->toCamel($resource),
            'columns' => $data->child
        ]);

        // 3. Generate Backend Assets
        if ($backendFramework === 'laravel') {
            $base = "$project_dir/backend";
            @mkdir("$base/app/Http/Controllers/Api", 0777, true);
            @mkdir("$base/app/Models", 0777, true);
            @mkdir("$base/database/migrations", 0777, true);
            @mkdir("$base/routes", 0777, true);
            
            @mkdir("$base/app/Http/Middleware", 0777, true);
            @mkdir("$base/tests/Feature", 0777, true);
            
            file_put_contents("$base/app/Http/Controllers/Api/{$context['resourceUpper']}Controller.php", $this->engine->render("backends/laravel/controller.twig", $context));
            file_put_contents("$base/app/Models/{$context['resourceUpper']}.php", $this->engine->render("backends/laravel/model.twig", $context));
            file_put_contents("$base/app/Http/Middleware/{$context['resourceUpper']}Middleware.php", $this->engine->render("backends/laravel/middleware.twig", $context));
            file_put_contents("$base/tests/Feature/{$context['resourceUpper']}Test.php", $this->engine->render("backends/laravel/test.twig", $context));
            file_put_contents("$base/database/migrations/" . date('Y_m_d_His') . "_create_{$resource}_table.php", $this->engine->render("backends/laravel/migration.twig", $context));
            // For routes, we might want to overwrite or append. Let's overwrite for simplicity in this version
            file_put_contents("$base/routes/api.php", $this->engine->render("backends/laravel/routes.twig", $context));
        } elseif ($backendFramework === 'express') {
            $base = "$project_dir/backend";
            @mkdir("$base/controllers", 0777, true);
            @mkdir("$base/models", 0777, true);
            @mkdir("$base/routes", 0777, true);

            @mkdir("$base/middlewares", 0777, true);
            @mkdir("$base/tests", 0777, true);
 
            file_put_contents("$base/controllers/{$context['resourceUpper']}Controller.js", $this->engine->render("backends/express/controller.twig", $context));
            file_put_contents("$base/models/{$context['resourceUpper']}.js", $this->engine->render("backends/express/model.twig", $context));
            file_put_contents("$base/middlewares/{$context['resourceUpper']}Middleware.js", $this->engine->render("backends/express/middleware.twig", $context));
            file_put_contents("$base/tests/{$context['resourceUpper']}.test.js", $this->engine->render("backends/express/test.twig", $context));
            file_put_contents("$base/routes/{$resource}.js", $this->engine->render("backends/express/controller.twig", $context)); // Using controller.twig as it contains routes in express template
        } elseif ($backendFramework === 'go') {
            $base = "$project_dir/backend";
            @mkdir("$base/controllers", 0777, true);
            @mkdir("$base/models", 0777, true);
            @mkdir("$base/services", 0777, true);

            @mkdir("$base/middlewares", 0777, true);
            @mkdir("$base/tests", 0777, true);
 
            file_put_contents("$base/controllers/{$context['resourceUpper']}Controller.go", $this->engine->render("backends/go/controller.twig", $context));
            file_put_contents("$base/models/{$context['resourceUpper']}.go", $this->engine->render("backends/go/model.twig", $context));
            file_put_contents("$base/services/{$context['resourceUpper']}Service.go", $this->engine->render("backends/go/service.twig", $context));
            file_put_contents("$base/middlewares/{$context['resourceUpper']}Middleware.go", $this->engine->render("backends/go/middleware.twig", $context));
            file_put_contents("$base/tests/{$context['resourceUpper']}_test.go", $this->engine->render("backends/go/test.twig", $context));
        } elseif ($backendFramework === 'adonis') {
            $base = "$project_dir/backend";
            @mkdir("$base/app/Controllers/Http", 0777, true);
            @mkdir("$base/app/Models", 0777, true);

            @mkdir("$base/app/Middleware", 0777, true);
            @mkdir("$base/tests/functional", 0777, true);
 
            file_put_contents("$base/app/Controllers/Http/{$context['resourceUpper']}Controller.ts", $this->engine->render("backends/adonis/controller.twig", $context));
            file_put_contents("$base/app/Models/{$context['resourceUpper']}.ts", $this->engine->render("backends/adonis/model.twig", $context));
            file_put_contents("$base/app/Middleware/{$context['resourceUpper']}Middleware.ts", $this->engine->render("backends/adonis/middleware.twig", $context));
            file_put_contents("$base/tests/functional/{$context['resourceUpper']}.spec.ts", $this->engine->render("backends/adonis/test.twig", $context));
        }

        // 4. Generate Frontend Assets
        $fe_base = "$project_dir/frontend/src/components";
        if (!is_dir($fe_base)) mkdir($fe_base, 0777, true);

        if ($frontendFramework === 'vue3') {
            file_put_contents("$fe_base/{$context['resourceUpper']}.vue", $this->engine->render("frontends/vue3/component.vue.twig", $context));
        } elseif ($frontendFramework === 'react') {
            file_put_contents("$fe_base/{$context['resourceUpper']}List.jsx", $this->engine->render("frontends/react/component.jsx.twig", $context));
        } elseif ($frontendFramework === 'svelte') {
            file_put_contents("$fe_base/{$context['resourceUpper']}.svelte", $this->engine->render("frontends/svelte/component.svelte.twig", $context));
        }

        return "Successfully generated assets for $resource";
    }

    private function ensureBaseProjectFiles($project_dir, $context)
    {
        // Root files
        file_put_contents("$project_dir/docker-compose.yml", $this->engine->render("docker-compose.yml.twig", $context));
        file_put_contents("$project_dir/README.md", $this->engine->render("README.md.twig", $context));

        // Backend base
        $be = "$project_dir/backend";
        if (!is_dir($be)) mkdir($be, 0777, true);
        
        $bf = $context['backend_framework'];
        if ($bf === 'laravel') {
            file_put_contents("$be/Dockerfile", $this->engine->render("backends/laravel/Dockerfile.twig", $context));
            file_put_contents("$be/.env", $this->engine->render("backends/laravel/.env.twig", $context));
        } elseif ($bf === 'express') {
            file_put_contents("$be/Dockerfile", $this->engine->render("backends/express/Dockerfile.twig", $context));
            file_put_contents("$be/app.js", $this->engine->render("backends/express/app.js.twig", $context));
            file_put_contents("$be/package.json", $this->engine->render("backends/express/package.json.twig", $context));
        } elseif ($bf === 'go') {
            file_put_contents("$be/Dockerfile", $this->engine->render("backends/go/Dockerfile.twig", $context));
            file_put_contents("$be/main.go", $this->engine->render("backends/go/main.go.twig", $context));
        } elseif ($bf === 'adonis') {
            @mkdir("$be/start", 0777, true);
            file_put_contents("$be/Dockerfile", $this->engine->render("backends/adonis/Dockerfile.twig", $context));
            file_put_contents("$be/.env", $this->engine->render("backends/adonis/.env.twig", $context));
            file_put_contents("$be/package.json", $this->engine->render("backends/adonis/package.json.twig", $context));
            file_put_contents("$be/start/routes.ts", $this->engine->render("backends/adonis/routes.ts.twig", $context));
        }

        // Frontend base
        $fe = "$project_dir/frontend";
        if (!is_dir("$fe/src")) mkdir("$fe/src", 0777, true);
        
        $ff = $context['frontend_framework'];
        file_put_contents("$fe/index.html", $this->engine->render("frontends/index.html.twig", $context));
        file_put_contents("$fe/Dockerfile", $this->engine->render("frontends/Dockerfile.twig", $context));

        if ($ff === 'vue3') {
            file_put_contents("$fe/src/App.vue", $this->engine->render("frontends/vue3/App.vue.twig", $context));
            file_put_contents("$fe/src/main.js", $this->engine->render("frontends/vue3/main.js.twig", $context));
            file_put_contents("$fe/src/router.js", $this->engine->render("frontends/vue3/router.js.twig", $context));
            file_put_contents("$fe/src/quasar-variables.sass", $this->engine->render("frontends/vue3/quasar-variables.sass.twig", $context));
            file_put_contents("$fe/package.json", $this->engine->render("frontends/vue3/package.json.twig", $context));
            file_put_contents("$fe/vite.config.js", $this->engine->render("frontends/vue3/vite.config.js.twig", $context));
        } elseif ($ff === 'react') {
            file_put_contents("$fe/src/App.jsx", $this->engine->render("frontends/react/App.jsx.twig", $context));
            file_put_contents("$fe/src/main.jsx", $this->engine->render("frontends/react/main.jsx.twig", $context));
            file_put_contents("$fe/package.json", $this->engine->render("frontends/react/package.json.twig", $context));
            file_put_contents("$fe/vite.config.js", $this->engine->render("frontends/react/vite.config.js.twig", $context));
        } elseif ($ff === 'svelte') {
            file_put_contents("$fe/src/App.svelte", $this->engine->render("frontends/svelte/App.svelte.twig", $context));
            file_put_contents("$fe/src/main.js", $this->engine->render("frontends/svelte/main.js.twig", $context));
            file_put_contents("$fe/package.json", $this->engine->render("frontends/svelte/package.json.twig", $context));
            file_put_contents("$fe/vite.config.js", $this->engine->render("frontends/svelte/vite.config.js.twig", $context));
        }
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
            $previews[] = ['name' => "Middleware.php", 'language' => 'php', 'content' => $this->engine->render("backends/laravel/middleware.twig", $context)];
            $previews[] = ['name' => "Test.php", 'language' => 'php', 'content' => $this->engine->render("backends/laravel/test.twig", $context)];
        } elseif ($backendFramework === 'adonis') {
            $previews[] = ['name' => "Controller.ts", 'language' => 'typescript', 'content' => $this->engine->render("backends/adonis/controller.twig", $context)];
            $previews[] = ['name' => "Middleware.ts", 'language' => 'typescript', 'content' => $this->engine->render("backends/adonis/middleware.twig", $context)];
            $previews[] = ['name' => "Test.ts", 'language' => 'typescript', 'content' => $this->engine->render("backends/adonis/test.twig", $context)];
        } elseif ($backendFramework === 'express') {
            $previews[] = ['name' => "Controller.js", 'language' => 'javascript', 'content' => $this->engine->render("backends/express/controller.twig", $context)];
            $previews[] = ['name' => "Middleware.js", 'language' => 'javascript', 'content' => $this->engine->render("backends/express/middleware.twig", $context)];
            $previews[] = ['name' => "Test.js", 'language' => 'javascript', 'content' => $this->engine->render("backends/express/test.twig", $context)];
        } elseif ($backendFramework === 'go' || $backendFramework === 'golang') {
            $previews[] = ['name' => "controller.go", 'language' => 'go', 'content' => $this->engine->render("backends/go/controller.twig", $context)];
            $previews[] = ['name' => "service.go", 'language' => 'go', 'content' => $this->engine->render("backends/go/service.twig", $context)];
            $previews[] = ['name' => "model.go", 'language' => 'go', 'content' => $this->engine->render("backends/go/model.twig", $context)];
            $previews[] = ['name' => "middleware.go", 'language' => 'go', 'content' => $this->engine->render("backends/go/middleware.twig", $context)];
            $previews[] = ['name' => "test.go", 'language' => 'go', 'content' => $this->engine->render("backends/go/test.twig", $context)];
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
