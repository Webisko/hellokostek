<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(\Illuminate\Foundation\Vite::class, function ($mock) {
            $mock->shouldReceive('__invoke')->andReturn(new \Illuminate\Support\HtmlString(''));
            $mock->shouldReceive('isRunningHot')->andReturn(false);
        });
    }
}
