<?php

namespace Tests\Unit;

use App\Http\Controllers\SharedTemplateController;
use ReflectionClass;
use Tests\TestCase;

class SharedTemplateControllerDefragmentRunsTest extends TestCase
{
    private function callDefragmentRuns(string $xml): string
    {
        $controller = new SharedTemplateController();
        $ref = new ReflectionClass($controller);
        $method = $ref->getMethod('defragmentRuns');
        $method->setAccessible(true);

        return (string) $method->invoke($controller, $xml);
    }

    public function test_defragment_runs_keeps_complex_paragraph_unchanged(): void
    {
        $xml = '<w:document><w:body>'
            . '<w:p>'
            . '<w:r><w:t>Abidjan, le </w:t></w:r>'
            . '<w:r><w:t>{{</w:t></w:r>'
            . '<w:r><w:t>DATE</w:t></w:r>'
            . '<w:r><w:t>}}</w:t></w:r>'
            . '<w:r><w:drawing><wp:inline/></w:drawing></w:r>'
            . '</w:p>'
            . '</w:body></w:document>';

        $out = $this->callDefragmentRuns($xml);

        $this->assertSame($xml, $out, 'A complex paragraph must not be rewritten.');
    }

    public function test_defragment_runs_merges_fragmented_placeholder_in_simple_paragraph(): void
    {
        $xml = '<w:document><w:body>'
            . '<w:p>'
            . '<w:r><w:rPr><w:b/></w:rPr><w:t>[</w:t></w:r>'
            . '<w:r><w:t>DATE</w:t></w:r>'
            . '<w:r><w:t>]</w:t></w:r>'
            . '</w:p>'
            . '</w:body></w:document>';

        $out = $this->callDefragmentRuns($xml);

        $this->assertStringContainsString('<w:t>[DATE]</w:t>', $out);
        $this->assertStringNotContainsString('<w:t>[</w:t>', $out);
        $this->assertStringContainsString('<w:rPr><w:b/></w:rPr>', $out);
    }
}
