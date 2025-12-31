<?php

use Cdoebler\LaravelUserSwitcher\Services\LaravelUserAdapterService;
use Cdoebler\LaravelUserSwitcher\Tests\Models\User;

it('can get id from adapter', function () {
    $user = new User(['id' => 123]);
    $adapter = new LaravelUserAdapterService($user);

    expect($adapter->getIdentifier())->toBe(123);
});

it('can get display name from name attribute', function () {
    $user = new User(['id' => 123, 'name' => 'John Doe']);
    $adapter = new LaravelUserAdapterService($user);

    expect($adapter->getDisplayName())->toBe('John Doe');
});

it('can get display name from email attribute', function () {
    $user = new User(['id' => 123, 'email' => 'john@example.com']);
    $adapter = new LaravelUserAdapterService($user);

    expect($adapter->getDisplayName())->toBe('john@example.com');
});

it('defaults to id if no display name available', function () {
    $user = new User(['id' => 123]);
    $adapter = new LaravelUserAdapterService($user);

    expect($adapter->getDisplayName())->toBe('123');
});
