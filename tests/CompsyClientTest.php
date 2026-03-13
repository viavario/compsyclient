<?php

declare (strict_types = 1);

namespace viavario\compsyclient\tests;

use PHPUnit\Framework\TestCase;
use viavario\compsyclient\CompsyClient;
use viavario\compsyclient\CompsyResult;

class CompsyClientTest extends TestCase
{
    public function testParseHtmlReturnsResultOnValidHtml(): void
    {
        $client = new CompsyClient();

        $html = <<<HTML
        <html><body>
            <div class="psy-item">
                <span class="psy-name">Dr. Jane Doe</span>
                <a class="psy-detail-link" href="/nl_BE/psychologist/jane-doe">View profile</a>
                <div class="psy-item-infos">Active</div>
            </div>
        </body></html>
        HTML;

        $result = $this->parseHtml($client, $html);

        $this->assertInstanceOf(CompsyResult::class, $result);
        $this->assertSame('Dr. Jane Doe', $result->name);
        $this->assertStringContainsString('jane-doe', $result->detailUrl);
        $this->assertSame('Active', $result->status);
    }

    public function testParseHtmlReturnsNullWhenNoResults(): void
    {
        $client = new CompsyClient();
        $html   = '<html><body><p>No results found.</p></body></html>';

        $result = $this->parseHtml($client, $html);

        $this->assertNull($result);
    }

    /**
     * Use reflection to call the private parseHtml method.
     */
    private function parseHtml(CompsyClient $client, string $html): ?CompsyResult
    {
        $method = new \ReflectionMethod(CompsyClient::class, 'parseHtml');
        $method->setAccessible(true);

        return $method->invoke($client, $html);
    }
}
