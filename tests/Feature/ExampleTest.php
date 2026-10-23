<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('Welcome'));
    }
}
