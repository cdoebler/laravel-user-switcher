<?php

namespace Cdoebler\LaravelUserSwitcher\Http\Middleware;

use Cdoebler\GenericUserSwitcher\Interfaces\ImpersonatorInterface;
use Cdoebler\LaravelUserSwitcher\Helpers\ConfigHelper;
use Cdoebler\LaravelUserSwitcher\Services\AuthorizationRequestResolver;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;

class HandleUserSwitcherMiddleware
{
    public function __construct(
        private readonly ConfigHelper $configHelper,
        private readonly AuthorizationRequestResolver $authResolver
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->has('_switch_user') && $this->canSwitchUser($request)) {
            $impersonator = resolve(ImpersonatorInterface::class);

            try {
                $switchUser = $request->get('_switch_user');
                if ($switchUser === '_stop') {
                    $impersonator->stopImpersonating();
                } elseif (is_string($switchUser) || is_int($switchUser)) {
                    $impersonator->impersonate($switchUser);
                }
            } catch (InvalidArgumentException $e) {
                if (app()->environment('local')) {
                    throw $e;
                }

                return redirect($request->url())->with('error', 'Invalid user identifier: ' . $e->getMessage());
            }

            return redirect($request->url());
        }

        return $next($request);
    }

    protected function canSwitchUser(Request $request): bool
    {
        $authorizationCallback = config('user-switcher.authorization_callback');

        if (is_callable($authorizationCallback)) {
            return (bool) $authorizationCallback($this->authResolver->getRequestForAuthorization($request));
        }

        return $this->configHelper->isEnabled();
    }
}
