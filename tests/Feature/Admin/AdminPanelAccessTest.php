<?php

use App\Models\User;

const ADMIN_PANEL_ENDPOINT = '/admin';

it('lets an admin user access the panel', function () {
    $admin = User::factory()->create([
        'password' => 'password',
        'is_admin' => true,
    ]);

    $this->actingAs($admin, 'web')
        ->get(ADMIN_PANEL_ENDPOINT)
        ->assertOk();
});

it('denies a non-admin authenticated user', function () {
    $user = User::factory()->create([
        'password' => 'password',
        'is_admin' => false,
    ]);

    $this->actingAs($user, 'web')
        ->get(ADMIN_PANEL_ENDPOINT)
        ->assertForbidden();
});

it('redirects a guest to the login page', function () {
    $this->get(ADMIN_PANEL_ENDPOINT)
        ->assertRedirect('/admin/login');
});
