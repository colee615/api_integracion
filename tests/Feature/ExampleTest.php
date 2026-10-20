<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_homepage_loads(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertViewIs('welcome');
    }
}
