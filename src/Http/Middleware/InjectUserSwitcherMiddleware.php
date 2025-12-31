<?php

namespace Cdoebler\LaravelUserSwitcher\Http\Middleware;

use Cdoebler\GenericUserSwitcher\Interfaces\ImpersonatorInterface;
use Cdoebler\GenericUserSwitcher\Interfaces\RendererInterface;
use Cdoebler\LaravelUserSwitcher\Services\AuthorizationRequestResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InjectUserSwitcherMiddleware
{
    public function __construct(
        private readonly RendererInterface $renderer,
        private readonly AuthorizationRequestResolver $authResolver
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (!config('user-switcher.auto_inject', true)) {
            return $response;
        }

        if (!$this->canInjectWidget($request)) {
            return $response;
        }

        if (!$response instanceof Response) {
            return $response;
        }

        $content = $response->getContent();
        if (!is_string($content) || !str_contains($content, '</body>')) {
            return $response;
        }

        $widget = $this->renderer->render(['current_user_id' => auth()->id()]);
        if (empty($widget)) {
            return $response;
        }

        $content = str_replace('</body>', $widget . '</body>', $content);
        $response->setContent($content);

        return $response;
    }

    protected function canInjectWidget(Request $request): bool
    {
        $authorizationCallback = config('user-switcher.authorization_callback');

        if (is_callable($authorizationCallback)) {
            return (bool) $authorizationCallback($this->authResolver->getRequestForAuthorization($request));
        }

        if (!config('user-switcher.enabled', true)) {
            return false;
        }

        $environments = config('user-switcher.environments', '*');
        if ($environments !== '*' && is_string($environments) && !in_array(config('app.env'), explode(',', $environments))) {
            return false;
        }

        return true;
    }
}
