<?php

namespace Tests\Unit;

use Saola\Core\Http\Controllers\{AdminController, ApiController, BaseApiController, BaseWebController, WebController};
use Tests\TestCase;

/** Core ships one base controller per context; projects and packages extend these, module controllers extend the project's. */
class ContextControllersTest extends TestCase
{
    public function test_each_context_has_a_base_controller(): void
    {
        foreach ([[WebController::class, BaseWebController::class, 'web'], [AdminController::class, BaseWebController::class, 'admin'], [ApiController::class, BaseApiController::class, 'api']] as [$class, $parent, $context]) {
            $this->assertTrue(is_subclass_of($class, $parent), $class);
            $this->assertSame($context, (new \ReflectionClass($class))->getDefaultProperties()['context']);
        }
    }
}
