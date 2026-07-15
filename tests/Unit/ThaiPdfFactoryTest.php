<?php

namespace Tests\Unit;

use App\Services\Pdf\ThaiPdfFactory;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

class ThaiPdfFactoryTest extends TestCase
{
    public function test_dictionary_mode_uses_conservative_thai_justification(): void
    {
        config()->set('pdf.thai_line_breaker', 'dictionary');
        $mpdf = app(ThaiPdfFactory::class)->make();

        $this->assertTrue($mpdf->useDictionaryLBR);
        $this->assertSame(0, $mpdf->fontdata['thsarabunnew135zws']['useOTL']);
        $this->assertSame(0.15, $mpdf->jSWord);
        $this->assertSame(0.55, $mpdf->jSmaxChar);
        $this->assertSame(0.0, $mpdf->jSmaxCharLast);
        $this->assertSame(0.0, $mpdf->jSmaxWordLast);
        $this->assertFalse($mpdf->justifyB4br);
    }

    #[RequiresPhpExtension('intl')]
    public function test_intl_fallback_uses_th_sarabun_new_and_disables_dictionary_to_prevent_double_processing(): void
    {
        config()->set('pdf.thai_line_breaker', 'intl');
        $factory = app(ThaiPdfFactory::class);
        $mpdf = $factory->make();
        $html = $factory->prepareHtml('<p class="thai-distributed">ภาษาไทยสำหรับทดสอบการตัดคำ</p>');

        $this->assertFalse($mpdf->useDictionaryLBR);
        $this->assertSame(0, $mpdf->fontdata['thsarabunnew135zws']['useOTL']);
        $this->assertSame('THSarabunNew.ttf', $mpdf->fontdata['thsarabunnew135zws']['R']);
        $this->assertSame('THSarabunNew-Bold.ttf', $mpdf->fontdata['thsarabunnew135zws']['B']);
        $this->assertFalse($mpdf->useSubstitutions);
        $this->assertStringContainsString("\u{200B}", $html);
        $this->assertStringContainsString('<html lang="th">', $html);
        $this->assertStringContainsString('<body lang="th">', $html);
    }
}
