<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Saola\Core\View\Compilers\MarkerRegistryDirectiveService;

class MarkerRegistryDirectiveServiceTest extends TestCase
{
    protected MarkerRegistryDirectiveService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MarkerRegistryDirectiveService();
    }

    // ─── parseArguments (via reflection) ────────────────────────────

    protected function callParseArguments(string $expression): array
    {
        $ref = new \ReflectionMethod($this->service, 'parseArguments');
        return $ref->invoke($this->service, $expression);
    }

    public function test_parseArguments_single_param()
    {
        $parts = $this->callParseArguments("'view'");
        $this->assertCount(1, $parts);
        $this->assertEquals("'view'", trim($parts[0]));
    }

    public function test_parseArguments_two_params()
    {
        $parts = $this->callParseArguments("'block', 'sidebar'");
        $this->assertCount(2, $parts);
        $this->assertEquals("'block'", trim($parts[0]));
        $this->assertEquals("'sidebar'", trim($parts[1]));
    }

    public function test_parseArguments_three_params_with_array()
    {
        $parts = $this->callParseArguments("'block', 'sidebar', ['key' => 'val']");
        $this->assertCount(3, $parts);
        $this->assertEquals("'block'", trim($parts[0]));
        $this->assertEquals("'sidebar'", trim($parts[1]));
        $this->assertEquals("['key' => 'val']", trim($parts[2]));
    }

    public function test_parseArguments_nested_function_call()
    {
        $parts = $this->callParseArguments("'block', fn(\$a, \$b)");
        $this->assertCount(2, $parts);
        $this->assertEquals("'block'", trim($parts[0]));
        $this->assertEquals("fn(\$a, \$b)", trim($parts[1]));
    }

    // ─── compileOpenMarkerDirective ─────────────────────────────────

    public function test_open_view_without_id()
    {
        $result = $this->service->compileOpenMarkerDirective("'view'");

        // name = 'view', no registryId → $__m_id = $__VIEW_ID__
        $this->assertStringContainsString("\$__m_name = 'view'", $result);
        $this->assertStringContainsString("\$__m_id = \$__VIEW_ID__", $result);
        $this->assertStringContainsString('$__helper->startMarker($__m_name, $__m_id', $result);
    }

    public function test_open_view_with_custom_id()
    {
        $result = $this->service->compileOpenMarkerDirective("'view', 'custom-id'");

        // name = 'view', registryId = 'custom-id' → $__m_id = $__m_rid (not prefixed)
        $this->assertStringContainsString("\$__m_rid = 'custom-id'", $result);
        $this->assertStringContainsString("if (\$__m_name === 'view') { \$__m_id = \$__m_rid;", $result);
    }

    public function test_open_block_without_id()
    {
        $result = $this->service->compileOpenMarkerDirective("'block'");

        // name = 'block', no registryId → $__m_id = '' (empty)
        $this->assertStringContainsString("\$__m_name = 'block'", $result);
        $this->assertStringContainsString("\$__m_id = ''", $result);
    }

    public function test_open_block_with_id()
    {
        $result = $this->service->compileOpenMarkerDirective("'block', 'sidebar'");

        // name = 'block', registryId = 'sidebar' → $__m_id = $__VIEW_ID__ . '-' . $__m_rid
        $this->assertStringContainsString("\$__m_rid = 'sidebar'", $result);
        $this->assertStringContainsString("\$__m_id = \$__VIEW_ID__ . '-' . \$__m_rid", $result);
    }

    public function test_open_block_with_id_and_attrs()
    {
        $result = $this->service->compileOpenMarkerDirective("'block', 'sidebar', ['a' => 1]");

        $this->assertStringContainsString("\$__m_rid = 'sidebar'", $result);
        $this->assertStringContainsString("\$__m_id = \$__VIEW_ID__ . '-' . \$__m_rid", $result);
        $this->assertStringContainsString("startMarker(\$__m_name, \$__m_id, ['a' => 1])", $result);
    }

    public function test_open_default_attrs_is_empty_array()
    {
        $result = $this->service->compileOpenMarkerDirective("'view'");

        // No attrs provided → defaults to []
        $this->assertStringContainsString('startMarker($__m_name, $__m_id, [])', $result);
    }

    // ─── compileCloseMarkerDirective ────────────────────────────────

    public function test_close_view_without_id()
    {
        $result = $this->service->compileCloseMarkerDirective("'view'");

        $this->assertStringContainsString("\$__m_name = 'view'", $result);
        $this->assertStringContainsString("\$__m_id = \$__VIEW_ID__", $result);
        $this->assertStringContainsString('$__helper->endMarker($__m_name, $__m_id)', $result);
    }

    public function test_close_view_with_custom_id()
    {
        $result = $this->service->compileCloseMarkerDirective("'view', 'custom-id'");

        $this->assertStringContainsString("\$__m_rid = 'custom-id'", $result);
        $this->assertStringContainsString("if (\$__m_name === 'view') { \$__m_id = \$__m_rid;", $result);
    }

    public function test_close_block_without_id()
    {
        $result = $this->service->compileCloseMarkerDirective("'block'");

        $this->assertStringContainsString("\$__m_name = 'block'", $result);
        $this->assertStringContainsString("\$__m_id = ''", $result);
    }

    public function test_close_block_with_id()
    {
        $result = $this->service->compileCloseMarkerDirective("'block', 'sidebar'");

        $this->assertStringContainsString("\$__m_rid = 'sidebar'", $result);
        $this->assertStringContainsString("\$__m_id = \$__VIEW_ID__ . '-' . \$__m_rid", $result);
        $this->assertStringContainsString('$__helper->endMarker($__m_name, $__m_id)', $result);
    }

    // ─── Symmetry: open & close produce matching IDs ────────────────

    public function test_open_and_close_produce_same_id_logic_for_view()
    {
        $open = $this->service->compileOpenMarkerDirective("'view', 'abc'");
        $close = $this->service->compileCloseMarkerDirective("'view', 'abc'");

        // Both should use $__m_rid directly for view
        $this->assertStringContainsString("\$__m_id = \$__m_rid", $open);
        $this->assertStringContainsString("\$__m_id = \$__m_rid", $close);
    }

    public function test_open_and_close_produce_same_id_logic_for_block()
    {
        $open = $this->service->compileOpenMarkerDirective("'block', 'nav'");
        $close = $this->service->compileCloseMarkerDirective("'block', 'nav'");

        // Both should prefix with $__VIEW_ID__ for non-view
        $this->assertStringContainsString("\$__VIEW_ID__ . '-' . \$__m_rid", $open);
        $this->assertStringContainsString("\$__VIEW_ID__ . '-' . \$__m_rid", $close);
    }

    // ─── Output is valid PHP ────────────────────────────────────────

    public function test_output_starts_with_php_tag()
    {
        $result = $this->service->compileOpenMarkerDirective("'view'");
        $this->assertStringStartsWith('<?php ', $result);
        $this->assertStringEndsWith('?>', $result);
    }

    public function test_close_output_starts_with_php_tag()
    {
        $result = $this->service->compileCloseMarkerDirective("'view'");
        $this->assertStringStartsWith('<?php ', $result);
        $this->assertStringEndsWith('?>', $result);
    }
}
