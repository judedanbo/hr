<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_up_endpoint_is_reachable_by_a_guest(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
        $this->assertNull($response->headers->get('Location'));
    }

    public function test_up_endpoint_fails_when_the_database_is_unreachable(): void
    {
        config(['app.debug' => false]);

        DB::shouldReceive('connection->getPdo')
            ->andThrow(new PDOException('could not find driver'));

        $response = $this->get('/up');

        $response->assertStatus(500);
    }
}
