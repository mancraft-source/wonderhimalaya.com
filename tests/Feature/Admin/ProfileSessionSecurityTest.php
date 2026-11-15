<?php

use App\Filament\Pages\Auth\EditProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\Pages\Login as LoginPage;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->sales()->create()->fresh();
});

function addSession(string $id, ?int $userId, string $ip, string $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/605.1.15 Version/17.0 Safari/605.1.15'): void
{
    DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'ip_address' => $ip, 'user_agent' => $userAgent, 'payload' => '', 'last_activity' => now()->getTimestamp()]);
}

it('records the time and IP of each sign-in and keeps the previous one', function () {
    $this->freezeSecond();
    $this->user->forceFill(['last_login_at' => now()->subDays(2), 'last_login_ip' => '203.0.113.9'])->save();

    request()->server->set('REMOTE_ADDR', '198.51.100.7');
    event(new Login('web', $this->user->fresh(), false));

    expect($this->user->fresh())
        ->last_login_at->toEqual(now())
        ->last_login_ip->toBe('198.51.100.7')
        ->previous_login_at->toEqual(now()->subDays(2))
        ->previous_login_ip->toBe('203.0.113.9');
});

it('records the sign-in when logging in through the admin login form', function () {
    Livewire::test(LoginPage::class)
        ->fillForm(['email' => $this->user->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect($this->user->fresh()->last_login_at)->not->toBeNull()
        ->and($this->user->fresh()->last_login_ip)->toBe('127.0.0.1');
});

it('lists only the signed-in user’s own sessions and marks this device', function () {
    $other = User::factory()->sales()->create();
    addSession('mine-other-device', $this->user->id, '198.51.100.20', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Version/17.0 Mobile/15E148 Safari/604.1');
    addSession('someone-else', $other->id, '192.0.2.99');

    $this->actingAs($this->user);
    addSession(session()->getId(), $this->user->id, '198.51.100.10');

    $sessions = Livewire::test(EditProfile::class)
        ->assertSee(['198.51.100.20', 'Mobile · Safari · iOS', '198.51.100.10', 'This device'])
        ->assertDontSee('192.0.2.99')
        ->instance()
        ->activeSessions();

    expect($sessions)->toHaveCount(2)
        ->and($sessions->where('is_current', true)->pluck('ip')->all())->toBe(['198.51.100.10']);
});

it('requires the current password to sign out other devices', function () {
    addSession('mine-other-device', $this->user->id, '198.51.100.20');

    Livewire::actingAs($this->user)
        ->test(EditProfile::class)
        ->callAction('signOutOtherDevices', data: ['password' => 'wrong-password'])
        ->assertHasActionErrors(['password']);

    expect(DB::table('sessions')->where('id', 'mine-other-device')->exists())->toBeTrue();
});

it('signs out every other device with the right password', function () {
    $other = User::factory()->sales()->create();
    addSession('mine-other-device', $this->user->id, '198.51.100.20');
    addSession('mine-laptop', $this->user->id, '198.51.100.21');
    addSession('someone-else', $other->id, '192.0.2.99');

    $this->actingAs($this->user);
    addSession(session()->getId(), $this->user->id, '198.51.100.10');
    $passwordHash = $this->user->password;

    Livewire::test(EditProfile::class)
        ->callAction('signOutOtherDevices', data: ['password' => 'password'])
        ->assertHasNoActionErrors()
        ->assertNotified('Signed out of other devices');

    expect(DB::table('sessions')->where('user_id', $this->user->id)->pluck('id')->all())->toBe([session()->getId()])
        ->and(DB::table('sessions')->where('id', 'someone-else')->exists())->toBeTrue()
        ->and($this->user->fresh()->password)->not->toBe($passwordHash);
});

it('signs out a single other session but never someone else’s', function () {
    $other = User::factory()->sales()->create();
    addSession('mine-other-device', $this->user->id, '198.51.100.20');
    addSession('someone-else', $other->id, '192.0.2.99');

    Livewire::actingAs($this->user)
        ->test(EditProfile::class)
        ->callAction(TestAction::make('signOutSession')->arguments(['session' => EditProfile::sessionKey('someone-else')]))
        ->assertNotified('That session has already ended')
        ->callAction(TestAction::make('signOutSession')->arguments(['session' => EditProfile::sessionKey('mine-other-device')]))
        ->assertNotified('Session signed out');

    expect(DB::table('sessions')->where('id', 'mine-other-device')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', 'someone-else')->exists())->toBeTrue();
});
