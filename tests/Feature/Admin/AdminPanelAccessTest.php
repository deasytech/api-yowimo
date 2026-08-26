<?php

use App\Models\User;

it('lets an admin user access the panel', function () {
    $admin = User::factory()->create([
        'password' => 'password',
        'is_admin' => true,
    ]);

    $this->actingAs($admin, 'web')
        ->get('/admin')
        ->assertOk();
});

it('denies a non-admin authenticated user', function () {
    $user = User::factory()->create([
        'password' => 'password',
        'is_admin' => false,
    ]);

    $this->actingAs($user, 'web')
        ->get('/admin')
        ->assertForbidden();
});

it('redirects a guest to the login page', function () {
    $this->get('/admin')
        ->assertRedirect('/admin/login');
});
