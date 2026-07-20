<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\InvoiceCreated;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TransactionalMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_a_password_reset_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'admin@example.ch']);

        $this->post('/mot-de-passe-oublie', ['email' => $user->email])
            ->assertRedirect()->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_request_does_not_reveal_an_unknown_email(): void
    {
        Notification::fake();
        $this->post('/mot-de-passe-oublie', ['email' => 'inconnu@example.ch'])
            ->assertRedirect()->assertSessionHas('success');
        Notification::assertNothingSent();
    }
}
