<?php

use App\Models\LoginActivity;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    Permission::findOrCreate('view dashboard');

    $user = User::factory()->create();
    $user->givePermissionTo('view dashboard');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    expect(LoginActivity::query()->where('user_id', $user->id)->where('status', 'success')->count())->toBe(1);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
    expect(LoginActivity::query()->where('email', $user->email)->where('status', 'failed')->count())->toBe(1);
});

test('inactive users can not authenticate', function () {
    Role::findOrCreate('CS');

    $user = User::factory()->create([
        'email' => 'inactive@example.com',
        'is_active' => false,
    ]);
    $user->assignRole('CS');

    $response = $this->post('/login', [
        'email' => 'inactive@example.com',
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasErrors('email')
        ->assertRedirect();

    $this->assertGuest();
    expect(LoginActivity::query()
        ->where('user_id', $user->id)
        ->where('status', 'failed')
        ->where('failure_reason', 'inactive_account')
        ->count())->toBe(1);
});

test('users can logout', function () {
    Permission::findOrCreate('view dashboard');

    $user = User::factory()->create();
    $user->givePermissionTo('view dashboard');

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response = $this->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
    expect(LoginActivity::query()
        ->where('user_id', $user->id)
        ->where('status', 'success')
        ->whereNotNull('logged_out_at')
        ->count())->toBe(1);
});
