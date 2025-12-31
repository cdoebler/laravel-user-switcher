<?php

namespace Cdoebler\LaravelUserSwitcher\Services;

use Cdoebler\GenericUserSwitcher\Interfaces\UserInterface;
use Illuminate\Contracts\Auth\Authenticatable;

class LaravelUserAdapterService implements UserInterface
{
    public function __construct(
        private readonly Authenticatable $user
    ) {
    }

    public function getIdentifier(): string|int
    {
        /** @var string|int */
        return $this->user->getAuthIdentifier();
    }

    public function getDisplayName(): string
    {
        if (method_exists($this->user, 'getDisplayName')) {
            $displayName = $this->user->getDisplayName();
            return $this->normalizeDisplayName($displayName);
        }

        foreach (['name', 'username', 'email'] as $attribute) {
            if (isset($this->user->$attribute)) {
                return $this->normalizeDisplayName($this->user->$attribute);
            }
        }

        return (string) $this->getIdentifier();
    }

    public function getOriginalUser(): Authenticatable
    {
        return $this->user;
    }

    private function normalizeDisplayName(mixed $value): string
    {
        return is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
    }
}
