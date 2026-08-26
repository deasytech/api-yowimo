<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('allows an admin to view horizon outside the local environment', function () {
    app()['env'] = 'production';

    $admin = User::factory()->create(['is_admin' => true]);
    $nonAdmin = User::factory()->create(['is_admin' => false]);

    expect(Gate::forUser($admin)->allows('viewHorizon'))->toBeTrue()
        ->and(Gate::forUser($nonAdmin)->allows('viewHorizon'))->toBeFalse();
});
