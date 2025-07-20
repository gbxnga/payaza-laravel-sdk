<?php

declare(strict_types=1);

namespace PayazaSdk\Traits;

use PayazaSdk\Enums\Environment;

trait ResolvesUrls
{
    /**
     * Resolve a URL from config with tenant support
     */
    private function resolveUrl(string $key): string
    {
        $url = config("payaza.urls.{$key}");
        
        if (!$url) {
            throw new \InvalidArgumentException("URL configuration for '{$key}' not found");
        }
        
        // Replace {tenant} placeholder with actual tenant
        $tenant = $this->env === Environment::LIVE ? 'live' : 'test';
        
        return str_replace('{tenant}', $tenant, $url);
    }
    
    /**
     * Get tenant ID for headers (used in some requests)
     */
    private function getTenantId(): string
    {
        return $this->env === Environment::LIVE ? 'live' : 'test';
    }
}