<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    /**
     * Discovered alongside the other pages under app/Filament/Pages — it must
     * never appear as a navigation item (it is wired up via the panel's
     * ->login() method instead).
     */
    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): string|Htmlable|null
    {
        return 'Welcome back to Yowimo';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Sign in to manage the Yowimo platform.';
    }
}
