<?php

namespace Tests\Unit;

use App\Http\Controllers\Controller;
use PHPUnit\Framework\TestCase;

class ControllerCapabilitiesTest extends TestCase
{
    public function test_base_controller_supports_authorization(): void
    {
        $this->assertTrue(method_exists(Controller::class, 'authorize'));
        $this->assertTrue(method_exists(Controller::class, 'authorizeResource'));
    }

    public function test_base_controller_supports_validation(): void
    {
        $this->assertTrue(method_exists(Controller::class, 'validate'));
        $this->assertTrue(method_exists(Controller::class, 'validateWithBag'));
    }
}
