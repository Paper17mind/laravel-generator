<?php

namespace App;

class ConfigBuilder
{
    private $config = [];
    
    public function __construct()
    {
        $this->initializeDefaults();
    }
    
    private function initializeDefaults()
    {
        $this->config = [
            'projectName' => '',
            'resourceName' => '',
            'backend' => [
                'framework' => 'laravel',
                'components' => [
                    'controller' => true,
                    'model' => true,
                    'migration' => true,
                    'routes' => true,
                    'middleware' => false,
                    'unitTest' => false,
                ]
            ],
            'frontend' => [
                'framework' => 'vue3',
                'uiFramework' => 'tailwind',
                'components' => [
                    'page' => true,
                    'composable' => true,
                    'service' => true,
                ]
            ],
            'database' => [
                'columns' => [
                    ['name' => 'id', 'type' => 'integer', 'nullable' => false],
                ]
            ]
        ];
    }
    
    public function fromRequest(array $data): self
    {
        $this->config = array_merge_recursive($this->config, $data);
        return $this;
    }
    
    public function build(): array
    {
        return $this->config;
    }
    
    public function setProjectName(string $name): self
    {
        $this->config['projectName'] = $name;
        return $this;
    }
    
    public function setResourceName(string $name): self
    {
        $this->config['resourceName'] = $name;
        return $this;
    }
    
    public function setBackendFramework(string $framework): self
    {
        $this->config['backend']['framework'] = $framework;
        return $this;
    }
}
