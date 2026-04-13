<?php

use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::findOrCreate('view dashboard');
});

test('users with forced password reset are redirected to password settings', function () {
    $user = User::factory()->create([
        'force_password_reset' => true,
    ]);
    $user->givePermissionTo('view dashboard');

    $response = $this->actingAs($user)->get('/dashboard');

    $response
        ->assertRedirect('/settings/password')
        ->assertSessionHas('error');
});

test('forced password reset can be completed without current password', function () {
    $user = User::factory()->create([
        'force_password_reset' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/settings/password')
        ->put('/settings/password', [
            'current_password' => '',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/password');

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
    expect($user->force_password_reset)->toBeFalse();
});

test('users can revoke other active sessions', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    LoginActivity::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'status' => 'success',
        'session_id' => 'other-session-id',
        'ip_address' => '127.0.0.2',
        'user_agent' => 'Test Browser',
        'logged_in_at' => now()->subMinutes(5),
    ]);

    DB::table('sessions')->insert([
        'id' => 'other-session-id',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.2',
        'user_agent' => 'Test Browser',
        'payload' => base64_encode(serialize([])),
        'last_activity' => now()->timestamp,
    ]);

    $response = $this
        ->from('/settings/security')
        ->delete('/settings/security/sessions', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/security');

    expect(DB::table('sessions')->where('id', 'other-session-id')->exists())->toBeFalse();
    expect(LoginActivity::query()
        ->where('session_id', 'other-session-id')
        ->whereNotNull('logged_out_at')
        ->exists())->toBeTrue();
});

test('security settings page shows login activity entries', function () {
    $user = User::factory()->create();

    LoginActivity::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'status' => 'success',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Browser Agent',
        'logged_in_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/settings/security');

    $response->assertOk();
});
