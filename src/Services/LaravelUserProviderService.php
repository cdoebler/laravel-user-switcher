<?php

namespace Cdoebler\LaravelUserSwitcher\Services;

use Cdoebler\GenericUserSwitcher\Interfaces\UserInterface;
use Cdoebler\GenericUserSwitcher\Interfaces\UserProviderInterface;
use Cdoebler\LaravelUserSwitcher\Helpers\ConfigHelper;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config as LaravelConfig;

class LaravelUserProviderService implements UserProviderInterface
{
    public function __construct(
        private readonly ConfigHelper $config
    ) {
    }

    /**
     * @return UserInterface[]
     */
    public function getUsers(): array
    {
        if (!$this->config->isEnabled()) {
            return [];
        }

        $modelClass = $this->getUserModelClass();

        if ($modelClass === null) {
            return [];
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Model&Authenticatable> $users */
        $users = $modelClass::all();

        return $users->map(fn($user) => new LaravelUserAdapterService($user))->all();
    }

    public function findUserById(string|int $identifier): ?UserInterface
    {
        if (!$this->config->isEnabled()) {
            return null;
        }

        $modelClass = $this->getUserModelClass();

        if ($modelClass === null) {
            return null;
        }

        $user = $modelClass::find($identifier);

        if (!$user instanceof Authenticatable) {
            return null;
        }

        return new LaravelUserAdapterService($user);
    }

    /**
     * @return class-string<Model&Authenticatable>|null
     */
    private function getUserModelClass(): ?string
    {
        $modelClass = LaravelConfig::get('user-switcher.user_model', 'App\\Models\\User');

        if (!is_string($modelClass) || !class_exists($modelClass)) {
            return null;
        }

        /** @var class-string<Model&Authenticatable> $modelClass */
        return $modelClass;
    }
}
