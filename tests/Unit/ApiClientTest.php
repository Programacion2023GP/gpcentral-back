<?php

namespace Tests\Unit;

use App\Models\ApiClient;
use App\Models\System;
use Tests\TestCase;

class ApiClientTest extends TestCase
{
    public function test_ability_must_be_explicit_or_wildcard(): void
    {
        $client = new ApiClient(['abilities' => ['employees:read']]);

        $this->assertTrue($client->hasAbility('employees:read'));
        $this->assertFalse($client->hasAbility('shared-images:write'));

        $client->abilities = ['*'];
        $this->assertTrue($client->hasAbility('shared-images:write'));
    }

    public function test_revoked_expired_or_inactive_clients_are_not_usable(): void
    {
        $system = new System(['active' => true, 'is_active' => true]);
        $client = new ApiClient(['active' => true, 'expires_at' => now()->addDay()]);
        $client->setRelation('system', $system);

        $this->assertTrue($client->isUsable());

        $client->revoked_at = now();
        $this->assertFalse($client->isUsable());

        $client->revoked_at = null;
        $client->expires_at = now()->subMinute();
        $this->assertFalse($client->isUsable());
    }
}
