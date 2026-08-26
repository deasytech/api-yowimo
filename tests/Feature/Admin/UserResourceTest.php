<?php

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'password' => 'password',
        'is_admin' => true,
    ]);
    $this->actingAs($this->admin, 'web');
});

it('lets an admin edit a user profile and toggle admin access', function () {
    $user = User::factory()->create(['display_name' => 'Original Name', 'is_admin' => false]);

    Livewire::test(EditUser::class, ['record' => $user->getKey()])
        ->fillForm([
            'display_name' => 'Updated Name',
            'is_admin' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->display_name)->toBe('Updated Name')
        ->and($user->is_admin)->toBeTrue();
});

it('does not offer deletion of users from the panel', function () {
    expect(UserResource::canCreate())->toBeFalse();

    $user = User::factory()->create();

    expect(UserResource::canDelete($user))->toBeFalse()
        ->and(UserResource::canDeleteAny())->toBeFalse();
});
