<?php

return [
    // Documentation title.
    'title' => 'API Documentation',
    'routes' => [
        // Route to Swagger UI.
        'documentation' => 'docs',
        // Route to Swagger UI assets proxy.
        'assets' => 'docs/assets',
        // Route to Swagger JSON spec download.
        'download' => 'docs/downloads/swagger',
        // Route to OAuth2 Callback.
        'oauth2-callback' => 'docs/oauth2-callback',
    ],
    'swagger-json-file' => storage_path('app') . DIRECTORY_SEPARATOR . 'swagger.json',
    'download-filename' => 'api.json',
    // Declare any middleware to run before loading documentation or download.
    // For example, security checks.
    'middleware' => [
        'all' => [],
        'documentation' => [],
        'assets' => [],
        'download' => [],
        'oauth2-callback' => [],
    ],
];
