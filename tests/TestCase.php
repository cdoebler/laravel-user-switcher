<?php

namespace Cdoebler\LaravelUserSwitcher\Tests;

use Cdoebler\LaravelUserSwitcher\Providers\UserSwitcherServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            UserSwitcherServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
    }
}
