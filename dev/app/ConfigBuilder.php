<?php

namespace app;

class ConfigBuilder
{
    private $config = [];
    
    public function __construct(array $initialData = [])
    {
        $this->initializeDefaults($initialData);
    }
    
    private function initializeDefaults(array $data)
    {
        $this->config = [
            'projectName' => $data['name'] ?? '',
            'backend' => [
                'framework' => $data['backend_framework'] ?? 'laravel',
                'components' => [
                    'controller' => true,
                    'model' => true,
                    'migration' => true,
                    'routes' => true,
                ]
            ],
            'frontend' => [
                'framework' => $data['frontend_framework'] ?? 'vue3',
                'components' => [
                    'page' => true,
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
    
    public function getBackendFramework(): string
    {
        return $this->config['backend']['framework'];
    }

    public function getFrontendFramework(): string
    {
        return $this->config['frontend']['framework'];
    }
}
