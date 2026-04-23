<?php

namespace App;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

class TemplateEngine
{
    private $config;
    private $twig;
    private $templates_path;
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->templates_path = __DIR__ . '/templates';
        
        $loader = new FilesystemLoader($this->templates_path);
        $this->twig = new Environment($loader, [
            'cache' => false,
            'auto_reload' => true,
            'strict_variables' => false,
        ]);
        
        $this->registerCustomFilters();
    }
    
    private function registerCustomFilters()
    {
        $this->twig->addFilter(new TwigFilter('studly_case', function($str) {
            return str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
        }));
        
        $this->twig->addFilter(new TwigFilter('camel_case', function($str) {
            return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $str))));
        }));
        
        $this->twig->addFilter(new TwigFilter('plural', function($str) {
            return $str . 's';
        }));
    }
    
    public function generate(): array
    {
        $generated = [];
        
        if ($this->config['backend']['components']['controller'] ?? false) {
            $generated['backend'] = $this->generateBackend();
        }
        
        if ($this->config['frontend']['components']['page'] ?? false) {
            $generated['frontend'] = $this->generateFrontend();
        }
        
        return $generated;
    }
    
    public function preview(): array
    {
        return $this->generate();
    }
    
    private function generateBackend(): array
    {
        $framework = $this->config['backend']['framework'];
        $resource = $this->config['resourceName'];
        $columns = $this->config['database']['columns'];
        
        $files = [];
        
        $context = [
            'resource' => $resource,
            'resourceUpper' => $this->toStudly($resource),
            'resourcePlural' => $this->toPlural($resource),
            'resourceCamel' => $this->toCamel($resource),
            'columns' => $columns,
        ];
        
        if ($this->config['backend']['components']['controller'] ?? false) {
            try {
                $files['controller'] = [
                    'name' => $this->toStudly($resource) . 'Controller.php',
                    'content' => $this->twig->render("backends/{$framework}/controller.twig", $context)
                ];
            } catch (\Exception $e) {
                $files['controller'] = ['error' => $e->getMessage()];
            }
        }
        
        if ($this->config['backend']['components']['model'] ?? false) {
            try {
                $files['model'] = [
                    'name' => $this->toStudly($resource) . '.php',
                    'content' => $this->twig->render("backends/{$framework}/model.twig", $context)
                ];
            } catch (\Exception $e) {
                $files['model'] = ['error' => $e->getMessage()];
            }
        }
        
        if ($this->config['backend']['components']['migration'] ?? false) {
            try {
                $files['migration'] = [
                    'name' => "Create{$this->toStudly($resource)}Table.php",
                    'content' => $this->twig->render("backends/{$framework}/migration.twig", $context)
                ];
            } catch (\Exception $e) {
                $files['migration'] = ['error' => $e->getMessage()];
            }
        }
        
        if ($this->config['backend']['components']['routes'] ?? false) {
            try {
                $files['routes'] = [
                    'name' => 'routes.php',
                    'content' => $this->twig->render("backends/{$framework}/routes.twig", $context)
                ];
            } catch (\Exception $e) {
                $files['routes'] = ['error' => $e->getMessage()];
            }
        }
        
        return $files;
    }
    
    private function generateFrontend(): array
    {
        $framework = $this->config['frontend']['framework'];
        $resource = $this->config['resourceName'];
        $columns = $this->config['database']['columns'];
        
        $files = [];
        
        $context = [
            'resource' => $resource,
            'resourceUpper' => $this->toStudly($resource),
            'resourcePlural' => $this->toPlural($resource),
            'resourceCamel' => $this->toCamel($resource),
            'columns' => $columns,
        ];
        
        if ($this->config['frontend']['components']['page'] ?? false) {
            try {
                $ext = $this->getExtension($framework);
                $files['page'] = [
                    'name' => "{$this->toStudly($resource)}.{$ext}",
                    'content' => $this->twig->render("frontends/{$framework}/component.{$ext}.twig", $context)
                ];
            } catch (\Exception $e) {
                $files['page'] = ['error' => $e->getMessage()];
            }
        }
        
        return $files;
    }
    
    private function toStudly(string $str): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
    }
    
    private function toCamel(string $str): string
    {
        return lcfirst($this->toStudly($str));
    }
    
    private function toPlural(string $str): string
    {
        return $str . 's';
    }
    
    private function getExtension(string $framework): string
    {
        return match($framework) {
            'vue3' => 'vue',
            'react' => 'jsx',
            'svelte' => 'svelte',
            'alpine' => 'html',
            default => 'html'
        };
    }
}
