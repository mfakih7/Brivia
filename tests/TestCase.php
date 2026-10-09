<?php

namespace Tests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Signs in an active staff member with the given role. */
    protected function actingAsStaff(Role $role = Role::Owner, array $attributes = []): User
    {
        $user = User::factory()->create(['role' => $role] + $attributes);
        $this->actingAs($user);

        return $user;
    }
}
