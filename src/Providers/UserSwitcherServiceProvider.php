<?php

namespace Cdoebler\LaravelUserSwitcher\Providers;

use Cdoebler\GenericUserSwitcher\Interfaces\ImpersonatorInterface;
use Cdoebler\GenericUserSwitcher\Interfaces\RendererInterface;
use Cdoebler\GenericUserSwitcher\Interfaces\UserProviderInterface;
use Cdoebler\GenericUserSwitcher\Renderer\UserSwitcherRenderer;
use Cdoebler\LaravelUserSwitcher\Helpers\ConfigHelper;
use Cdoebler\LaravelUserSwitcher\Http\Middleware\HandleUserSwitcherMiddleware;
use Cdoebler\LaravelUserSwitcher\Http\Middleware\InjectUserSwitcherMiddleware;
use Cdoebler\LaravelUserSwitcher\Services\LaravelSessionImpersonatorService;
use Cdoebler\LaravelUserSwitcher\Services\LaravelUserProviderService;
use Cdoebler\LaravelUserSwitcher\Views\Components\UserSwitcherWidgetComponent;
use Illuminate\Support\ServiceProvider;

class UserSwitcherServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/user-switcher.php', 'user-switcher');

        $this->app->singleton(ConfigHelper::class);

        $this->app->bind(UserProviderInterface::class, LaravelUserProviderService::class);
        $this->app->bind(ImpersonatorInterface::class, LaravelSessionImpersonatorService::class);
        $this->app->singleton(RendererInterface::class, UserSwitcherRenderer::class);

        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make('router');
        $router->aliasMiddleware('user-switcher', HandleUserSwitcherMiddleware::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/user-switcher.php' => config_path('user-switcher.php'),
        ], 'config');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'user-switcher');

        $this->loadViewComponentsAs('user-switcher', [
            UserSwitcherWidgetComponent::class,
        ]);

        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app->make('router');
        $router->pushMiddlewareToGroup('web', HandleUserSwitcherMiddleware::class);
        $router->pushMiddlewareToGroup('web', InjectUserSwitcherMiddleware::class);
    }
}
