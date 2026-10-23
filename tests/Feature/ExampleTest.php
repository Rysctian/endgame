<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guests_enter_through_sign_in(): void
    {
        $this->get('/')->assertRedirect('/login');
    }
}
