<?php

use Cdoebler\GenericUserSwitcher\Renderer\UserSwitcherRenderer;
use Cdoebler\LaravelUserSwitcher\Services\LaravelUserProviderService;
use Cdoebler\LaravelUserSwitcher\Services\LaravelSessionImpersonatorService;
use Cdoebler\LaravelUserSwitcher\Helpers\ConfigHelper;
use Cdoebler\LaravelUserSwitcher\Tests\Models\User;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    config(['user-switcher.enabled' => true]);
    config(['user-switcher.environments' => '*']); // Allow all environments
    config(['user-switcher.user_model' => User::class]);
});

it('highlights current impersonated user when current_user_id is passed', function () {
    $originalUser = User::forceCreate(['id' => 1, 'name' => 'Original User', 'email' => 'original@example.com', 'password' => 'password']);
    $targetUser = User::forceCreate(['id' => 2, 'name' => 'Target User', 'email' => 'target@example.com', 'password' => 'password']);

    Auth::login($originalUser);

    $configHelper = resolve(ConfigHelper::class);
    $userProvider = resolve(LaravelUserProviderService::class);
    $impersonator = new LaravelSessionImpersonatorService($configHelper);

    $impersonator->impersonate($targetUser->id);

    // Current user should be Target User (id=2)
    expect(Auth::id())->toBe(2);
    expect($impersonator->getOriginalUserId())->toBe(1); // Original is user 1

    // Render with current_user_id set to the impersonated user
    $renderer = new UserSwitcherRenderer($userProvider, $impersonator);
    $html = $renderer->render(['current_user_id' => Auth::id()]);

    expect($html)->not->toBeEmpty();

    // Target User (currently impersonated) should be highlighted
    expect($html)->toMatch('/<li[^>]*cdoebler-gus-item-active[^>]*>.*Target User.*<\/li>/s');

    // Original User should NOT be highlighted
    expect($html)->not->toMatch('/<li[^>]*cdoebler-gus-item-active[^>]*>.*Original User.*<\/li>/s');
});

it('highlights current user when not impersonating', function () {
    $user = User::forceCreate(['id' => 1, 'name' => 'Regular User', 'email' => 'regular@example.com', 'password' => 'password']);

    Auth::login($user);

    $configHelper = resolve(ConfigHelper::class);
    $userProvider = resolve(LaravelUserProviderService::class);
    $impersonator = new LaravelSessionImpersonatorService($configHelper);

    // Render with current_user_id set to the logged-in user
    $renderer = new UserSwitcherRenderer($userProvider, $impersonator);
    $html = $renderer->render(['current_user_id' => Auth::id()]);

    expect($html)->not->toBeEmpty();

    // Regular User should be highlighted since they're the current user
    expect($html)->toMatch('/<li[^>]*cdoebler-gus-item-active[^>]*>.*Regular User.*<\/li>/s');
});

it('highlights impersonated user when starting from logged-out state', function () {
    $targetUser = User::forceCreate(['id' => 5, 'name' => 'Target User', 'email' => 'target@example.com', 'password' => 'password']);
    $otherUser = User::forceCreate(['id' => 6, 'name' => 'Other User', 'email' => 'other@example.com', 'password' => 'password']);

    // Start not logged in
    expect(Auth::check())->toBeFalse();

    $configHelper = resolve(ConfigHelper::class);
    $userProvider = resolve(LaravelUserProviderService::class);
    $impersonator = new LaravelSessionImpersonatorService($configHelper);

    // Impersonate from logged-out state
    $impersonator->impersonate($targetUser->id);

    // Should now be logged in as target user
    expect(Auth::id())->toBe(5);
    expect($impersonator->isImpersonating())->toBeTrue();
    expect($impersonator->getOriginalUserId())->toBeNull();

    // Render with current_user_id set to the impersonated user
    $renderer = new UserSwitcherRenderer($userProvider, $impersonator);
    $html = $renderer->render(['current_user_id' => Auth::id()]);

    expect($html)->not->toBeEmpty();

    // Extract just the list items for debugging
    preg_match_all('/<li[^>]*>.*?<\/li>/s', $html, $matches);
    $listItems = $matches[0] ?? [];

    // Target User should be highlighted (even though we started logged out)
    expect($html)->toMatch('/<li[^>]*cdoebler-gus-item-active[^>]*>.*Target User.*<\/li>/s');

    // Other User should NOT be highlighted
    // Check each list item to ensure Other User is not in an active item
    foreach ($listItems as $item) {
        if (str_contains($item, 'Other User')) {
            expect($item)->not->toContain('cdoebler-gus-item-active');
        }
    }
});
