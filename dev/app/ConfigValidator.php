<?php

namespace App;

class ConfigValidator
{
    private $errors = [];
    
    public function validate(array $config): bool
    {
        $this->errors = [];
        
        if (empty($config['projectName'])) {
            $this->errors[] = 'Project name is required';
        }
        
        if (empty($config['resourceName'])) {
            $this->errors[] = 'Resource name is required';
        }
        
        $validBackends = ['laravel', 'adonis', 'express', 'go', 'python'];
        if (!in_array($config['backend']['framework'] ?? 'laravel', $validBackends)) {
            $this->errors[] = 'Invalid backend framework';
        }
        
        $validFrontends = ['html', 'vue3', 'react', 'svelte', 'alpine'];
        if (!in_array($config['frontend']['framework'] ?? 'vue3', $validFrontends)) {
            $this->errors[] = 'Invalid frontend framework';
        }
        
        if (empty($config['database']['columns'])) {
            $this->errors[] = 'At least one column is required';
        }
        
        return count($this->errors) === 0;
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
}
