@if(config('user-switcher.enabled') && in_array(config('app.env'), explode(',', config('user-switcher.environments'))))
    {!! app(\Cdoebler\GenericUserSwitcher\Renderer\UserSwitcherRenderer::class)->render(array_merge($attributes->getAttributes(), ['current_user_id' => auth()->id()])) !!}
@endif
