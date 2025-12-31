<?php

use Cdoebler\LaravelUserSwitcher\Services\LaravelSessionImpersonatorService;
use Cdoebler\LaravelUserSwitcher\Helpers\ConfigHelper;
use Cdoebler\LaravelUserSwitcher\Services\LaravelUserAdapterService;
use Cdoebler\LaravelUserSwitcher\Tests\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

it('can impersonate a user', function () {
    $originalUser = User::forceCreate(['id' => 1, 'name' => 'Original', 'email' => 'original@example.com', 'password' => 'password']);
    $targetUser = User::forceCreate(['id' => 2, 'name' => 'Target', 'email' => 'target@example.com', 'password' => 'password']);

    Auth::login($originalUser);

    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $impersonator = new LaravelSessionImpersonatorService($config);

    $impersonator->impersonate($targetUser->id);

    expect(Auth::id())->toBe(2);
    expect(Session::get('original_user_id'))->toBe(1);
    expect($impersonator->getOriginalUserId())->toBe(1);
    expect($impersonator->isImpersonating())->toBeTrue();
});

it('can stop impersonating', function () {
    $originalUser = User::forceCreate(['id' => 1, 'name' => 'Original', 'email' => 'original@example.com', 'password' => 'password']);
    $targetUser = User::forceCreate(['id' => 2, 'name' => 'Target', 'email' => 'target@example.com', 'password' => 'password']);

    Auth::login($originalUser);

    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $impersonator = new LaravelSessionImpersonatorService($config);

    $impersonator->impersonate($targetUser->id);

    $impersonator->stopImpersonating();

    expect(Auth::id())->toBe(1);
    expect(Session::has('original_user_id'))->toBeFalse();
    expect($impersonator->isImpersonating())->toBeFalse();
    expect($impersonator->getOriginalUserId())->toBeNull();
});

it('does not overwrite original user id when nesting impersonation', function () {
    $originalUser = User::forceCreate(['id' => 1, 'name' => 'Original', 'email' => 'original@example.com', 'password' => 'password']);
    $firstTarget = User::forceCreate(['id' => 2, 'name' => 'Target 1', 'email' => 'target1@example.com', 'password' => 'password']);
    $secondTarget = User::forceCreate(['id' => 3, 'name' => 'Target 2', 'email' => 'target2@example.com', 'password' => 'password']);

    Auth::login($originalUser);

    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $impersonator = new LaravelSessionImpersonatorService($config);

    $impersonator->impersonate($firstTarget->id);
    expect(Session::get('original_user_id'))->toBe(1);

    $impersonator->impersonate($secondTarget->id);
    expect(Session::get('original_user_id'))->toBe(1); // Should still be Original
    expect(Auth::id())->toBe(3);

    $impersonator->stopImpersonating();
    expect(Auth::id())->toBe(1); // Should return to Original
});

it('does nothing when stopping impersonation if not impersonating', function () {
    $user = User::forceCreate(['id' => 1, 'name' => 'User', 'email' => 'user@example.com', 'password' => 'password']);
    Auth::login($user);

    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $impersonator = new LaravelSessionImpersonatorService($config);

    expect(Session::has('original_user_id'))->toBeFalse();

    $impersonator->stopImpersonating();

    expect(Auth::id())->toBe(1);
    expect(Session::has('original_user_id'))->toBeFalse();
});

it('does not impersonate if disabled', function () {
    $originalUser = User::forceCreate(['id' => 1, 'name' => 'Original', 'email' => 'original@example.com', 'password' => 'password']);
    $targetUser = User::forceCreate(['id' => 2, 'name' => 'Target', 'email' => 'target@example.com', 'password' => 'password']);

    Auth::login($originalUser);

    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(false);

    $impersonator = new LaravelSessionImpersonatorService($config);

    $impersonator->impersonate($targetUser->id);

    expect(Auth::id())->toBe(1);
    expect(Session::has('original_user_id'))->toBeFalse();
    expect($impersonator->isImpersonating())->toBeFalse();
});

it('can impersonate when starting from logged-out state', function () {
    $targetUser = User::forceCreate(['id' => 2, 'name' => 'Target', 'email' => 'target@example.com', 'password' => 'password']);

    // Start not logged in
    expect(Auth::check())->toBeFalse();

    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $impersonator = new LaravelSessionImpersonatorService($config);

    $impersonator->impersonate($targetUser->id);

    // Should now be logged in as target user
    expect(Auth::id())->toBe(2);
    expect(Session::has('original_user_id'))->toBeTrue(); // Session key exists
    expect($impersonator->isImpersonating())->toBeTrue();
    expect($impersonator->getOriginalUserId())->toBeNull(); // But getOriginalUserId returns null
});

it('logs out when stopping impersonation from logged-out start', function () {
    $targetUser = User::forceCreate(['id' => 2, 'name' => 'Target', 'email' => 'target@example.com', 'password' => 'password']);

    // Start not logged in
    expect(Auth::check())->toBeFalse();

    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $impersonator = new LaravelSessionImpersonatorService($config);

    $impersonator->impersonate($targetUser->id);
    expect(Auth::id())->toBe(2);

    $impersonator->stopImpersonating();

    // Should be logged out, not logged in as null
    expect(Auth::check())->toBeFalse();
    expect(Session::has('original_user_id'))->toBeFalse();
    expect($impersonator->isImpersonating())->toBeFalse();
});

// Input Validation Tests
it('throws exception for empty user identifier', function () {
    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $impersonator = new LaravelSessionImpersonatorService($config);

    $impersonator->impersonate('');
})->throws(InvalidArgumentException::class, 'User identifier cannot be empty');

it('throws exception for whitespace-only identifier', function () {
    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $impersonator = new LaravelSessionImpersonatorService($config);

    $impersonator->impersonate('   ');
})->throws(InvalidArgumentException::class, 'User identifier cannot be empty');

it('throws exception for overly long identifier', function () {
    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $impersonator = new LaravelSessionImpersonatorService($config);

    $impersonator->impersonate(str_repeat('a', 256));
})->throws(InvalidArgumentException::class, 'User identifier cannot exceed 255 characters');

it('trims whitespace from string identifiers', function () {
    $user = User::forceCreate(['id' => 123, 'name' => 'User', 'email' => 'user@example.com', 'password' => 'password']);

    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $impersonator = new LaravelSessionImpersonatorService($config);

    $impersonator->impersonate('  123  ');

    expect(Auth::id())->toBe(123);
});

// Session Regeneration Tests
it('regenerates session ID when starting impersonation', function () {
    $user = User::forceCreate(['id' => 1, 'name' => 'User', 'email' => 'user@example.com', 'password' => 'password']);
    $targetUser = User::forceCreate(['id' => 2, 'name' => 'Target', 'email' => 'target@example.com', 'password' => 'password']);

    Auth::login($user);

    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $impersonator = new LaravelSessionImpersonatorService($config);

    $oldSessionId = Session::getId();
    $impersonator->impersonate($targetUser->id);
    $newSessionId = Session::getId();

    expect($newSessionId)->not->toBe($oldSessionId);
});

it('regenerates session ID when stopping impersonation', function () {
    $user = User::forceCreate(['id' => 1, 'name' => 'User', 'email' => 'user@example.com', 'password' => 'password']);
    $targetUser = User::forceCreate(['id' => 2, 'name' => 'Target', 'email' => 'target@example.com', 'password' => 'password']);

    Auth::login($user);

    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $impersonator = new LaravelSessionImpersonatorService($config);

    $impersonator->impersonate($targetUser->id);
    $sessionDuringImpersonation = Session::getId();

    $impersonator->stopImpersonating();
    $sessionAfterStopping = Session::getId();

    expect($sessionAfterStopping)->not->toBe($sessionDuringImpersonation);
});
