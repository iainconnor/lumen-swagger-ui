<?php

/** @var Laravel\Lumen\Application $app */
/** @noinspection PhpExpressionResultUnusedInspection */
$app;

/** @var Illuminate\Contracts\Config\Repository $config */
$config = $app->make('config');

$configName = \IainConnor\LumenSwaggerUi\LumenSwaggerUiServiceProvider::CONFIG_NAME;

$app->group(['middleware' => $config->get($configName . ".middleware.all")],
    function () use ($app, $config, $configName) {
        $app->get($config->get($configName . ".routes.documentation"), [
            'uses' => 'LumenSwaggerUiController@getDocumentation',
            'as' => $configName . '.getDocumentation',
            'middleware' => $config->get($configName . ".middleware.documentation"),
        ]);

        $app->get($config->get($configName . ".routes.assets") . "/{asset}", [
            'uses' => 'LumenSwaggerUiController@getAsset',
            'as' => $configName . '.getAsset',
            'middleware' => $config->get($configName . ".middleware.assets"),
        ]);

        $app->get($config->get($configName . ".routes.download") . "[/{file}]", [
            'uses' => 'LumenSwaggerUiController@getDownload',
            'as' => $configName . '.getDownload',
            'middleware' => $config->get($configName . ".middleware.download"),
        ]);

        $app->get($config->get($configName . ".routes.oauth2-callback"), [
            'uses' => 'LumenSwaggerUiController@getOAuth2Callback',
            'as' => $configName . '.getOAuth2Callback',
            'middleware' => $config->get($configName . ".middleware.oauth2-callback"),
        ]);
    });
