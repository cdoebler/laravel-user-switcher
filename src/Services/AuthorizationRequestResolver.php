<?php

namespace Cdoebler\LaravelUserSwitcher\Services;

use Cdoebler\GenericUserSwitcher\Interfaces\ImpersonatorInterface;
use Illuminate\Http\Request;

class AuthorizationRequestResolver
{
    public function __construct(
        private readonly ImpersonatorInterface $impersonator,
    ) {}

    public function getRequestForAuthorization(Request $request): Request
    {
        if (!$this->impersonator->isImpersonating()) {
            return $request;
        }

        $originalUserId = $this->impersonator->getOriginalUserId();
        if ($originalUserId === null) {
            return $request;
        }

        $userModel = config('user-switcher.user_model', 'App\\Models\\User');
        if (!is_string($userModel) || !class_exists($userModel)) {
            return $request;
        }

        /** @var class-string<\Illuminate\Database\Eloquent\Model> $userModel */

        $originalUser = $userModel::find($originalUserId);

        if ($originalUser === null) {
            return $request;
        }

        $clonedRequest = clone $request;
        $clonedRequest->setUserResolver(fn() => $originalUser);

        return $clonedRequest;
    }
}
