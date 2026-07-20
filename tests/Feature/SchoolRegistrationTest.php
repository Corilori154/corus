<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_school_account(): void
    {
        $this->post('/creer-mon-ecole', [
            'school_name' => 'Studio Lumière',
            'name' => 'Marie Dupont',
            'email' => 'marie@lumiere.test',
            'city' => 'Lausanne',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
        ])->assertRedirect('/admin');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('schools', ['slug' => 'studio-lumiere']);
        $this->assertDatabaseHas('users', ['email' => 'marie@lumiere.test', 'is_admin' => true]);
    }

    public function test_registration_page_stays_accessible_when_already_authenticated(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id, 'is_admin' => true]);

        $this->actingAs($user)
            ->get('/creer-mon-ecole')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Saas/Register'));
    }
}
