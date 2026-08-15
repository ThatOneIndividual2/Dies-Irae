<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\CreatesApplication;

abstract class LaravelTestCase extends BaseTestCase
{
    use CreatesApplication;
}
