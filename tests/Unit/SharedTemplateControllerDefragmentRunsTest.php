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

    private function callBuildLooseTokenPattern(string $token): string
    {
        $controller = new SharedTemplateController();
        $ref = new ReflectionClass($controller);
        $method = $ref->getMethod('buildLooseTokenPattern');
        $method->setAccessible(true);

        return (string) $method->invoke($controller, $token);
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

    public function test_defragment_runs_merges_placeholder_fragmented_by_proofErr(): void
    {
        // Word insère des <w:proofErr> (spell-check markers) entre les runs,
        // ce qui fragmente les variables {{ NOM DU DEMANDEUR}} en plusieurs <w:t>.
        $xml = '<w:document><w:body>'
            . '<w:p>'
            . '<w:r><w:rPr><w:sz w:val="28"/></w:rPr><w:t>{{ NOM</w:t></w:r>'
            . '<w:proofErr w:type="gramEnd"/>'
            . '<w:r><w:rPr><w:sz w:val="28"/></w:rPr><w:t xml:space="preserve"> DU DEMANDEUR}}</w:t></w:r>'
            . '</w:p>'
            . '</w:body></w:document>';

        $out = $this->callDefragmentRuns($xml);

        $this->assertStringContainsString('{{ NOM DU DEMANDEUR}}', $out,
            'Variable fragmented by proofErr must be merged into a single text node.');
        $this->assertStringNotContainsString('<w:proofErr', $out,
            'proofErr markers must be removed in rebuilt paragraph.');
    }

    public function test_loose_token_pattern_matches_fragmented_xml_and_typographic_apostrophe(): void
    {
        $pattern = $this->callBuildLooseTokenPattern("nom de l universite");
        $fragmented = "n</w:t></w:r><w:r><w:t>om de l’université";

        $this->assertSame(1, preg_match('~' . $pattern . '~iu', $fragmented),
            'Loose pattern must match token even when split by XML tags and typographic apostrophe.');
    }

}
