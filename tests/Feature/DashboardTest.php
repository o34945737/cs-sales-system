<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    Permission::findOrCreate('view dashboard');

    $user = User::factory()->create();
    $user->givePermissionTo('view dashboard');
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});
