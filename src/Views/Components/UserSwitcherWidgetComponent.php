<?php

namespace Cdoebler\LaravelUserSwitcher\Views\Components;

use Illuminate\View\Component;

class UserSwitcherWidgetComponent extends Component
{
    public function render(): \Illuminate\Contracts\View\View
    {
        /** @var view-string $viewName */
        $viewName = 'user-switcher::components.widget';
        return view($viewName);
    }
}
