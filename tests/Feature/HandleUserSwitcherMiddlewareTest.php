<?php

use Cdoebler\LaravelUserSwitcher\Http\Middleware\HandleUserSwitcherMiddleware;
use Cdoebler\LaravelUserSwitcher\Tests\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function () {
    config(['user-switcher.enabled' => true]);
    config(['user-switcher.environments' => 'local']);
    config(['app.env' => 'local']);
    config(['user-switcher.user_model' => User::class]);

    User::forceCreate(['id' => 1, 'name' => 'Regular User', 'email' => 'regular@example.com', 'password' => 'password']);
    User::forceCreate(['id' => 2, 'name' => 'Admin User', 'email' => 'admin@example.com', 'password' => 'password']);
});

it('uses ConfigHelper when authorization_callback is not set', function () {
    config(['user-switcher.authorization_callback' => null]);
    config(['user-switcher.enabled' => true]);
    config(['user-switcher.environments' => '*']); // Default allows all environments

    $middleware = resolve(HandleUserSwitcherMiddleware::class);
    $request = Request::create('/test?_switch_user=2', 'GET');

    $result = $middleware->handle($request, fn() => new Response());

    // When callback is null and environments is '*', switching is allowed
    expect($result)->toBeInstanceOf(RedirectResponse::class);
});

it('respects environments config when authorization_callback is not set', function () {
    config(['user-switcher.authorization_callback' => null]);
    config(['user-switcher.enabled' => true]);
    config(['user-switcher.environments' => 'production']); // Restrict to production only
    config(['app.env' => 'local']); // Current env is local

    $middleware = resolve(HandleUserSwitcherMiddleware::class);
    $request = Request::create('/test?_switch_user=2', 'GET');

    $result = $middleware->handle($request, fn() => new Response());

    // Environment doesn't match, so switching should be denied
    expect($result)->toBeInstanceOf(Response::class);
    expect($result)->not->toBeInstanceOf(RedirectResponse::class);
});

it('denies switching when authorization_callback returns false', function () {
    config(['user-switcher.authorization_callback' => fn($request) => false]);

    $middleware = resolve(HandleUserSwitcherMiddleware::class);
    $request = Request::create('/test?_switch_user=2', 'GET');

    $result = $middleware->handle($request, fn() => new Response());

    expect($result)->toBeInstanceOf(Response::class);
    expect($result)->not->toBeInstanceOf(RedirectResponse::class);
});

it('allows switching when authorization_callback returns true', function () {
    config(['user-switcher.authorization_callback' => fn($request) => true]);

    $middleware = resolve(HandleUserSwitcherMiddleware::class);
    $request = Request::create('/test?_switch_user=2', 'GET');

    $result = $middleware->handle($request, fn() => new Response());

    expect($result)->toBeInstanceOf(RedirectResponse::class);
});

it('passes request to authorization_callback', function () {
    $callbackInvoked = false;
    $receivedRequest = null;

    config(['user-switcher.authorization_callback' => function ($request) use (&$callbackInvoked, &$receivedRequest) {
        $callbackInvoked = true;
        $receivedRequest = $request;
        return true;
    }]);

    $middleware = resolve(HandleUserSwitcherMiddleware::class);
    $request = Request::create('/test?_switch_user=2', 'GET');

    $middleware->handle($request, fn() => new Response());

    expect($callbackInvoked)->toBeTrue();
    expect($receivedRequest)->toBe($request);
});

it('allows stopping impersonation when authorization_callback returns true', function () {
    config(['user-switcher.authorization_callback' => fn($request) => true]);

    $middleware = resolve(HandleUserSwitcherMiddleware::class);
    $request = Request::create('/test?_switch_user=_stop', 'GET');

    $result = $middleware->handle($request, fn() => new Response());

    expect($result)->toBeInstanceOf(RedirectResponse::class);
});

it('prioritizes authorization_callback over environment check', function () {
    config(['app.env' => 'production']);
    config(['user-switcher.authorization_callback' => fn($request) => true]);

    $middleware = resolve(HandleUserSwitcherMiddleware::class);
    $request = Request::create('/test?_switch_user=2', 'GET');

    $result = $middleware->handle($request, fn() => new Response());

    expect($result)->toBeInstanceOf(RedirectResponse::class);
});

// Note: Additional error handling and edge case tests exist in LaravelSessionImpersonatorTest
// which tests input validation, user existence checking, and error scenarios directly
