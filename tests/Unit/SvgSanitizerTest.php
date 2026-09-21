<?php

namespace Tests\Unit;

use App\Support\SvgSanitizer;
use PHPUnit\Framework\TestCase;

class SvgSanitizerTest extends TestCase
{
    public function test_removes_scripts_handlers_and_external_refs(): void
    {
        $dirty = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 10 10" onload="alert(1)">
  <script>alert('xss')</script>
  <a href="javascript:alert(2)"><rect width="5" height="5" onclick="evil()" fill="#f00"/></a>
  <image xlink:href="https://evil.example/track.png" width="1" height="1"/>
  <use href="#ok"/>
  <foreignObject><body><iframe src="https://evil.example"></iframe></body></foreignObject>
  <circle r="2" style="fill:url(https://evil.example/x)"/>
</svg>
SVG;
        $clean = SvgSanitizer::clean($dirty);

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onload', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('evil.example', $clean);
        $this->assertStringNotContainsString('foreignObject', $clean);
        $this->assertStringContainsString('<rect', $clean);
        $this->assertStringContainsString('href="#ok"', $clean);
    }

    public function test_rejects_non_svg(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SvgSanitizer::clean('<html><script>1</script></html>');
    }
}
