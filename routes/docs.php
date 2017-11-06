<?php

/** @var \Laravel\Lumen\Routing\Router $router */
/** @noinspection PhpExpressionResultUnusedInspection */
$router;

/** @var Laravel\Lumen\Application $app */
/** @noinspection PhpExpressionResultUnusedInspection */
$app = $router->app;

/** @var Illuminate\Contracts\Config\Repository $config */
$config = $app->make('config');

$configName = \IainConnor\LumenSwaggerUi\LumenSwaggerUiServiceProvider::CONFIG_NAME;

$router->group(['middleware' => $config->get($configName . ".middleware.all")],
    function () use ($router, $config, $configName) {
        $router->get($config->get($configName . ".routes.documentation"), [
            'uses' => 'LumenSwaggerUiController@getDocumentation',
            'as' => $configName . '.getDocumentation',
            'middleware' => $config->get($configName . ".middleware.documentation"),
        ]);

        $router->get($config->get($configName . ".routes.assets") . "/{asset}", [
            'uses' => 'LumenSwaggerUiController@getAsset',
            'as' => $configName . '.getAsset',
            'middleware' => $config->get($configName . ".middleware.assets"),
        ]);

        $router->get($config->get($configName . ".routes.download") . "[/{file}]", [
            'uses' => 'LumenSwaggerUiController@getDownload',
            'as' => $configName . '.getDownload',
            'middleware' => $config->get($configName . ".middleware.download"),
        ]);

        $router->get($config->get($configName . ".routes.oauth2-callback"), [
            'uses' => 'LumenSwaggerUiController@getOAuth2Callback',
            'as' => $configName . '.getOAuth2Callback',
            'middleware' => $config->get($configName . ".middleware.oauth2-callback"),
        ]);
    });
