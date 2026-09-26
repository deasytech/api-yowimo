<?php

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;

it('applies the Yowimo brand to the admin panel', function () {
    $panel = Filament::getPanel('admin');

    expect($panel->getBrandName())->toBe('Yowimo')
        ->and((string) $panel->getBrandLogo())->toContain('logo.svg')
        ->and($panel->getColors()['primary'])->toBe(Color::Violet)
        ->and($panel->getColors()['danger'])->toBe(Color::Rose)
        ->and($panel->getColors()['info'])->toBe(Color::Sky);
});

it('registers every navigation group, in order', function () {
    $labels = collect(Filament::getPanel('admin')->getNavigationGroups())
        ->map(fn ($group): ?string => $group->getLabel())
        ->all();

    expect($labels)->toBe([
        'Community',
        'Catalog',
        'Finance',
        'Gameplay',
        'Progression',
        'System',
    ]);
});

it('renders a branded login page', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Welcome back to Yowimo')
        ->assertSee('Sign in to manage the Yowimo platform.');
});

it('spreads the main content across the full width', function () {
    $panel = Filament::getPanel('admin');

    expect($panel->getMaxContentWidth())->toBe(Width::Full);

    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin, 'web')
        ->get('/admin')
        ->assertOk()
        ->assertSee('fi-width-full', false);
});

it('narrows the sidebar below the 20rem default', function () {
    expect(Filament::getPanel('admin')->getSidebarWidth())->toBe('17rem');
});

it('paints the sidebar in its own distinct shade', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('#f5f3ff', false)
        ->assertSee('#151024', false);
});
