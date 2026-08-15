<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $compiled = storage_path('framework/views-testing');
        if (!is_dir($compiled)) {
            mkdir($compiled, 0777, true);
        }
        config(['view.compiled' => $compiled]);
    }
}
