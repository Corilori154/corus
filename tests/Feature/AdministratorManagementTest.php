<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdministratorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_another_administrator_for_their_school(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id, 'is_admin' => true]);

        $this->actingAs($admin)->post('/admin/administrateurs', [
            'name' => 'Sophie Martin',
            'email' => 'sophie@example.ch',
            'password' => 'mot-de-passe-solide',
            'password_confirmation' => 'mot-de-passe-solide',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'school_id' => $school->id,
            'name' => 'Sophie Martin',
            'email' => 'sophie@example.ch',
            'is_admin' => true,
        ]);
    }

    public function test_admin_can_update_their_profile_and_password(): void
    {
        $admin = User::factory()->create(['school_id' => School::factory()->create()->id, 'is_admin' => true, 'password' => 'ancien-mot-de-passe']);

        $this->actingAs($admin)->put('/admin/mon-compte', [
            'name' => 'Nouveau Nom',
            'email' => 'nouveau@example.ch',
        ])->assertRedirect();

        $this->actingAs($admin)->put('/admin/mon-compte/mot-de-passe', [
            'current_password' => 'ancien-mot-de-passe',
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertRedirect();

        $this->assertSame('Nouveau Nom', $admin->fresh()->name);
        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $admin->fresh()->password));
    }

    public function test_admin_cannot_delete_themselves_or_an_admin_from_another_school(): void
    {
        $admin = User::factory()->create(['school_id' => School::factory()->create()->id, 'is_admin' => true]);
        $other = User::factory()->create(['school_id' => School::factory()->create()->id, 'is_admin' => true]);

        $this->actingAs($admin)->delete("/admin/administrateurs/{$admin->id}")->assertUnprocessable();
        $this->actingAs($admin)->delete("/admin/administrateurs/{$other->id}")->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('users', ['id' => $other->id]);
    }
}
