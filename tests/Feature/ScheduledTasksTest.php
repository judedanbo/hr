<?php

namespace Tests\Feature;

use Tests\TestCase;

class ScheduledTasksTest extends TestCase
{
    public function test_notifications_prune_is_registered_on_the_schedule(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('notifications:prune')
            ->assertSuccessful();
    }
}
