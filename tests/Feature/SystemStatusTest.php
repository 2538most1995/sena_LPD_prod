<?php

namespace Tests\Feature;

use Tests\TestCase;

class SystemStatusTest extends TestCase
{
    public function test_system_status_endpoint_is_available(): void
    {
        $response = $this->getJson('/api/v1/system/status');

        $response
            ->assertOk()
            ->assertJsonPath('data.database', 'connected')
            ->assertJsonStructure([
                'data' => ['application', 'environment', 'framework', 'database', 'checked_at'],
            ]);
    }
}
