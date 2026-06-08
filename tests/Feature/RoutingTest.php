<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test all the application routes to ensure they return a 200 OK and render correctly.
     */
    public function test_all_routes_return_successful_response(): void
    {
        $routes = [
            '/' => 'dashboard',
            '/upload' => 'upload',
            '/duplicates' => 'duplicates',
            '/warehouse' => 'warehouse',
            '/sql-assistant' => 'sql-assistant',
            '/etl-monitoring' => 'etl-monitoring',
            '/analytics' => 'analytics',
            '/studio/connections' => 'studio-connections',
            '/studio/pipelines' => 'studio-pipelines',
            '/studio/runs' => 'studio-runs',
            '/studio/schedules' => 'studio-schedules',
            '/studio/assistant' => 'studio-assistant',
            '/studio/monitoring' => 'studio-monitoring',
        ];

        foreach ($routes as $url => $tab) {
            $response = $this->get($url);
            $response->assertStatus(200);
            $response->assertViewHas('activeTab', $tab);
        }
    }
}
