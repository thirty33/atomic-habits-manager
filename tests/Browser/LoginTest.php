<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\LoginPage;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseTruncation;

    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'dusk@test.com',
            'is_admin' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function testIsLoginPage(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit(new LoginPage)
                ->isLoginPage($browser);
        });
    }

    public function testGuestIsRedirectedToLogin(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visitRoute('backoffice.dashboard.index')
                ->assertRouteIs('login');
        });
    }

    public function testAdminCanLogin(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit(new LoginPage)
                ->loginWithEmailAndPassword($this->user->email, 'password')
                ->assertRouteIs('backoffice.dashboard.index')
                ->waitForText($this->user->name)
                ->assertSee($this->user->email);
        });
    }
}