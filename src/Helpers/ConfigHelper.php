<?php

namespace Cdoebler\LaravelUserSwitcher\Helpers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config as LaravelConfig;

class ConfigHelper
{
    public function isEnabled(): bool
    {
        if (!LaravelConfig::get('user-switcher.enabled', true)) {
            return false;
        }

        $allowedEnvironments = LaravelConfig::get('user-switcher.environments', '*');

        if ($allowedEnvironments === '*') {
            return true;
        }

        if (is_string($allowedEnvironments)) {
            $allowedEnvironments = array_map(trim(...), explode(',', $allowedEnvironments));
        }

        if (!is_array($allowedEnvironments)) {
            return false;
        }

        return App::environment($allowedEnvironments);
    }
}
