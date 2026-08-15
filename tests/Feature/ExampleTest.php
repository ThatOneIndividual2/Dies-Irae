<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends LaravelTestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertRedirect();
        $this->get('/dev/inspect')->assertOk();
    }
}
