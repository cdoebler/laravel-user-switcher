<?php

namespace Cdoebler\LaravelUserSwitcher\Services;

use Cdoebler\GenericUserSwitcher\Interfaces\ImpersonatorInterface;
use Cdoebler\GenericUserSwitcher\Interfaces\UserInterface;
use Cdoebler\LaravelUserSwitcher\Helpers\ConfigHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;

class LaravelSessionImpersonatorService implements ImpersonatorInterface
{
    private const SESSION_KEY = 'original_user_id';
    private const NO_ORIGINAL_USER = '__no_original_user__';

    public function __construct(
        private readonly ConfigHelper $config
    ) {
    }

    public function impersonate(string|int $identifier): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        if (is_string($identifier)) {
            $identifier = trim($identifier);

            if ($identifier === '') {
                throw new InvalidArgumentException('User identifier cannot be empty.');
            }

            if (strlen($identifier) > 255) {
                throw new InvalidArgumentException('User identifier cannot exceed 255 characters.');
            }
        }

        if (!Session::has(self::SESSION_KEY)) {
            // Use a marker string if no user is logged in, so the session key exists
            Session::put(self::SESSION_KEY, Auth::id() ?? self::NO_ORIGINAL_USER);
        }

        if (!Auth::loginUsingId($identifier)) {
            // Rollback: remove session key we just set
            Session::forget(self::SESSION_KEY);
            throw new InvalidArgumentException(sprintf('User with identifier "%s" not found or cannot be authenticated.', $identifier));
        }

        Session::regenerate();
    }

    public function stopImpersonating(): void
    {
        if (!Session::has(self::SESSION_KEY)) {
            return;
        }

        $originalUserId = Session::get(self::SESSION_KEY);
        Session::forget(self::SESSION_KEY);

        // If original user was the marker (started from logged-out state), log out
        if ($originalUserId === self::NO_ORIGINAL_USER) {
            Auth::logout();
        } else {
            Auth::loginUsingId($originalUserId);
        }

        Session::regenerate();
    }

    public function isImpersonating(): bool
    {
        return Session::has(self::SESSION_KEY);
    }

    public function getOriginalUserId(): string|int|null
    {
        /** @var string|int|null $originalId */
        $originalId = Session::get(self::SESSION_KEY);

        // Return null if the marker is set (started from logged-out state)
        return $originalId === self::NO_ORIGINAL_USER ? null : $originalId;
    }
}
