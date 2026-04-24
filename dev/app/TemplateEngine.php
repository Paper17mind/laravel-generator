<?php

namespace app;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

class TemplateEngine
{
    private $twig;
    private $templates_path;
    
    public function __construct()
    {
        $this->templates_path = __DIR__ . '/../templates';
        
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
            return str_replace(' ', '', ucwords(str_replace('_', ' ', (string)$str)));
        }));
        
        $this->twig->addFilter(new TwigFilter('camel_case', function($str) {
            return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', (string)$str))));
        }));
        
        $this->twig->addFilter(new TwigFilter('plural', function($str) {
            return $str . 's'; // Simple pluralization for now
        }));
    }

    public function render(string $template, array $context)
    {
        return $this->twig->render($template, $context);
    }
}
