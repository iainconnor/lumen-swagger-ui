<?php

namespace IainConnor\LumenSwaggerUi;

use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application;

class LumenSwaggerUiServiceProvider extends ServiceProvider
{
    const CONFIG_NAME = "swaggerUi";

    public function register()
    {
        $this->app->configure(LumenSwaggerUiServiceProvider::CONFIG_NAME);
        $this->mergeConfigFrom($this->getConfigPath(), LumenSwaggerUiServiceProvider::CONFIG_NAME);

        // Note: I'm not totally clear on why this has to happen.
        // If I let the DI container inject these dependencies on its own, the config would be empty.
        $this->app->bind(LumenSwaggerUiController::class, function (Application $app) {
            return new LumenSwaggerUiController($app->make('files'), $app->make('request'), $app->make('config'));
        });

    }

    public function boot()
    {
        $this->publishes([$this->getConfigPath() => $this->app->basePath() .
                                                    "/config/" .
                                                    LumenSwaggerUiServiceProvider::CONFIG_NAME .
                                                    ".php"]);

        $this->app->group(['namespace' => 'IainConnor\LumenSwaggerUi'], function ($app) {
            require __DIR__ . "/../../routes/docs.php";
        });
    }

    private function getConfigPath()
    {
        return __DIR__ . "/../../config/swaggerUi.php";
    }
}
