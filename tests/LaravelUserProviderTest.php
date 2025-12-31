<?php

use Cdoebler\LaravelUserSwitcher\Services\LaravelUserProviderService;
use Cdoebler\LaravelUserSwitcher\Helpers\ConfigHelper;
use Cdoebler\LaravelUserSwitcher\Tests\Models\User;
use Illuminate\Support\Facades\Config as LaravelConfig;

it('returns empty array if model does not exist', function () {
    LaravelConfig::set('user-switcher.user_model', 'NonExistentModel');

    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $provider = new LaravelUserProviderService($config);
    expect($provider->getUsers())->toBeArray()->toBeEmpty();
});

// We need to setup a database for this one, or mock the static call.
// Since we are using Orchestra, we can use the in-memory sqlite database.


it('returns adapted users', function () {
    LaravelConfig::set('user-switcher.user_model', User::class);

    User::forceCreate(['id' => 1, 'name' => 'User 1', 'email' => 'user1@example.com', 'password' => 'password']);
    User::forceCreate(['id' => 2, 'name' => 'User 2', 'email' => 'user2@example.com', 'password' => 'password']);

    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $provider = new LaravelUserProviderService($config);
    $users = $provider->getUsers();

    expect($users)->toBeArray()->toHaveCount(2);
    expect($users[0]->getIdentifier())->toBe(1);
    expect($users[1]->getIdentifier())->toBe(2);
});

it('can find user by id', function () {
    LaravelConfig::set('user-switcher.user_model', User::class);

    User::forceCreate(['id' => 1, 'name' => 'Found', 'email' => 'found@example.com', 'password' => 'password']);

    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $provider = new LaravelUserProviderService($config);
    $user = $provider->findUserById(1);

    expect($user)->not->toBeNull();
    expect($user->getIdentifier())->toBe(1);
    expect($user->getDisplayName())->toBe('Found');
});

it('returns null if user not found', function () {
    LaravelConfig::set('user-switcher.user_model', User::class);

    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $provider = new LaravelUserProviderService($config);
    $user = $provider->findUserById(999);

    expect($user)->toBeNull();
});

it('returns empty array/null if config is invalid', function () {
    LaravelConfig::set('user-switcher.user_model', 123);

    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $provider = new LaravelUserProviderService($config);

    expect($provider->getUsers())->toBeArray()->toBeEmpty();
    expect($provider->findUserById(1))->toBeNull();
});

it('returns null if found user is not authenticatable', function () {
    // We need a model that exists but checks fail.
    // Since strict typing usually prevents returning a non-Authenticatable from a variable typed as such unless unchecked.
    // But find() returns mixed/object.
    // We can define a dummy class here.

    if (!class_exists('NonAuthenticatableUser')) {
        class NonAuthenticatableUser extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'users'; // Reuse users table
        }
    }


    LaravelConfig::set('user-switcher.user_model', 'NonAuthenticatableUser');

    User::forceCreate(['id' => 10, 'name' => 'Bad User', 'email' => 'bad@example.com', 'password' => 'password']);

    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(true);

    $provider = new LaravelUserProviderService($config);
    $user = $provider->findUserById(10);

    expect($user)->toBeNull();
});

it('returns empty array/null if disabled', function () {
    $config = Mockery::mock(ConfigHelper::class);
    $config->shouldReceive('isEnabled')->andReturn(false);

    $provider = new LaravelUserProviderService($config);

    expect($provider->getUsers())->toBeArray()->toBeEmpty();
    expect($provider->findUserById(1))->toBeNull();
});
