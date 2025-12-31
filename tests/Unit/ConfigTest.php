<?php

use Cdoebler\LaravelUserSwitcher\Helpers\ConfigHelper;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config as LaravelConfig;

it('is enabled by default (simulated)', function () {
    // Manually set defaults matching config file
    LaravelConfig::set('user-switcher.enabled', true);
    LaravelConfig::set('user-switcher.environments', '*');

    $config = new ConfigHelper();
    expect($config->isEnabled())->toBeTrue();
});

it('can be disabled via config', function () {
    LaravelConfig::set('user-switcher.enabled', false);

    $config = new ConfigHelper();
    expect($config->isEnabled())->toBeFalse();
});

it('respects environment restrictions (string)', function () {
    LaravelConfig::set('user-switcher.enabled', true);
    LaravelConfig::set('user-switcher.environments', 'local,staging');

    App::shouldReceive('environment')
        ->with(['local', 'staging'])
        ->andReturn(true);
    $config = new ConfigHelper();
    expect($config->isEnabled())->toBeTrue();
});

it('respects environment restrictions (array - mismatch)', function () {
    LaravelConfig::set('user-switcher.enabled', true);
    LaravelConfig::set('user-switcher.environments', ['local', 'staging']);

    App::shouldReceive('environment')
        ->with(['local', 'staging'])
        ->andReturn(false);

    $config = new ConfigHelper();
    expect($config->isEnabled())->toBeFalse();
});

it('respects environment restrictions (array - match)', function () {
    LaravelConfig::set('user-switcher.enabled', true);
    LaravelConfig::set('user-switcher.environments', ['local', 'staging']);

    App::shouldReceive('environment')
        ->with(['local', 'staging'])
        ->andReturn(true);

    $config = new ConfigHelper();
    expect($config->isEnabled())->toBeTrue();
});

it('allows all environments with wildcard', function () {
    LaravelConfig::set('user-switcher.enabled', true);
    LaravelConfig::set('user-switcher.environments', '*');

    App::shouldReceive('environment')->andReturn('production');
    $config = new ConfigHelper();
    expect($config->isEnabled())->toBeTrue();
});

it('returns false if environments config is invalid', function () {
    LaravelConfig::set('user-switcher.enabled', true);
    LaravelConfig::set('user-switcher.environments', 123);

    $config = new ConfigHelper();
    expect($config->isEnabled())->toBeFalse();
});
