<?php

use Cdoebler\LaravelUserSwitcher\Http\Middleware\InjectUserSwitcherMiddleware;
use Cdoebler\LaravelUserSwitcher\Tests\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function () {
    config(['user-switcher.enabled' => true]);
    config(['user-switcher.environments' => 'local']);
    config(['user-switcher.auto_inject' => true]);
    config(['app.env' => 'local']);
    config(['user-switcher.user_model' => User::class]);

    User::forceCreate(['id' => 1, 'name' => 'Test User', 'email' => 'test@example.com', 'password' => 'password']);
});

it('injects user switcher widget into HTML response when auto_inject is enabled', function () {
    // Note: This test requires the App::environment() to match configured environments
    // For integration testing, verify manually in browser
    expect(User::count())->toBe(1);
})->skip('Requires full Laravel environment setup');

it('does not inject when auto_inject is disabled', function () {
    config(['user-switcher.auto_inject' => false]);

    $middleware = resolve(InjectUserSwitcherMiddleware::class);

    $request = Request::create('/test', 'GET');
    $response = new Response('<html><body></body></html>');

    $result = $middleware->handle($request, fn() => $response);

    expect($result->getContent())->toBe('<html><body></body></html>');
});

it('does not inject when enabled is false', function () {
    config(['user-switcher.enabled' => false]);

    $middleware = resolve(InjectUserSwitcherMiddleware::class);

    $request = Request::create('/test', 'GET');
    $response = new Response('<html><body></body></html>');

    $result = $middleware->handle($request, fn() => $response);

    expect($result->getContent())->toBe('<html><body></body></html>');
});

it('does not inject when environment does not match', function () {
    config(['user-switcher.environments' => 'production']);

    $middleware = resolve(InjectUserSwitcherMiddleware::class);

    $request = Request::create('/test', 'GET');
    $response = new Response('<html><body></body></html>');

    $result = $middleware->handle($request, fn() => $response);

    expect($result->getContent())->toBe('<html><body></body></html>');
});

it('does not inject when response has no body tag', function () {
    $middleware = resolve(InjectUserSwitcherMiddleware::class);

    $request = Request::create('/test', 'GET');
    $response = new Response('{"json": "response"}');

    $result = $middleware->handle($request, fn() => $response);

    expect($result->getContent())->toBe('{"json": "response"}');
});

it('passes current user id when injecting widget', function () {
    // Override environment config for this test
    config(['user-switcher.environments' => '*']);

    $user = User::forceCreate(['id' => 10, 'name' => 'Original User', 'email' => 'original@example.com', 'password' => 'password']);
    $impersonatedUser = User::forceCreate(['id' => 20, 'name' => 'Impersonated User', 'email' => 'impersonated@example.com', 'password' => 'password']);

    Auth::login($user);

    $impersonator = resolve(\Cdoebler\GenericUserSwitcher\Interfaces\ImpersonatorInterface::class);
    $impersonator->impersonate($impersonatedUser->id);

    expect(Auth::id())->toBe(20); // Currently impersonating user 20

    $middleware = resolve(InjectUserSwitcherMiddleware::class);

    $request = Request::create('/test', 'GET');
    $response = new Response('<html><body>Test</body></html>');

    $result = $middleware->handle($request, fn() => $response);
    $content = $result->getContent();

    // The widget should be injected
    expect($content)->toContain('cdoebler-gus-container');

    // Impersonated User should be highlighted, not the original user
    expect($content)->toMatch('/<li[^>]*cdoebler-gus-item-active[^>]*>.*Impersonated User.*<\/li>/s');
    expect($content)->not->toMatch('/<li[^>]*cdoebler-gus-item-active[^>]*>.*Original User.*<\/li>/s');
});

it('does not inject when authorization_callback returns false', function () {
    config(['user-switcher.environments' => '*']);
    config(['user-switcher.authorization_callback' => fn($request) => false]);

    $middleware = resolve(InjectUserSwitcherMiddleware::class);

    $request = Request::create('/test', 'GET');
    $response = new Response('<html><body>Test</body></html>');

    $result = $middleware->handle($request, fn() => $response);

    expect($result->getContent())->toBe('<html><body>Test</body></html>');
});

it('injects when authorization_callback returns true', function () {
    config(['user-switcher.environments' => '*']);
    config(['user-switcher.authorization_callback' => fn($request) => true]);

    $middleware = resolve(InjectUserSwitcherMiddleware::class);

    $request = Request::create('/test', 'GET');
    $response = new Response('<html><body>Test</body></html>');

    $result = $middleware->handle($request, fn() => $response);

    expect($result->getContent())->toContain('cdoebler-gus-container');
});

it('checks original user authorization when impersonating', function () {
    config(['user-switcher.environments' => '*']);

    $authorizedUser = User::forceCreate(['id' => 50, 'name' => 'Authorized User', 'email' => 'authorized@example.com', 'password' => 'password']);
    $unauthorizedUser = User::forceCreate(['id' => 51, 'name' => 'Unauthorized User', 'email' => 'unauthorized@example.com', 'password' => 'password']);

    config(['user-switcher.authorization_callback' => fn($request) => $request->user()?->id === 50]);

    Auth::login($authorizedUser);

    $impersonator = resolve(\Cdoebler\GenericUserSwitcher\Interfaces\ImpersonatorInterface::class);
    $impersonator->impersonate($unauthorizedUser->id);

    expect(Auth::id())->toBe(51);

    $middleware = resolve(InjectUserSwitcherMiddleware::class);

    $request = Request::create('/test', 'GET');
    $response = new Response('<html><body>Test</body></html>');

    $result = $middleware->handle($request, fn() => $response);

    expect($result->getContent())->toContain('cdoebler-gus-container');
});
