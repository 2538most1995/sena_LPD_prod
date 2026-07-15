<?php

namespace Tests\Unit;

use App\Services\Pdf\ThaiLineBreaker;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

class ThaiLineBreakerTest extends TestCase
{
    private const TEXT = 'ด้วย ศูนย์ส่งเสริมการเรียนรู้ระดับอำเภอเสนา กำหนดจัดโครงการอาหารว่างสร้างรายได้ให้แก่ประชาชน ณ สกร.ระดับอำเภอเสนา หมู่ที่ 2 ตำบลวังสำโรง อำเภอบางมูลนาก ในระหว่างวันที่ 12 กรกฎาคม 2569 ถึง 12 กรกฎาคม 2569 ตั้งแต่เวลา 09:00 น. ถึง 15:00 น. จำนวนผู้เรียนตามบัญชีรายชื่อ โดยมีวัตถุประสงค์เพื่อพัฒนาทักษะด้านอาชีพให้แก่ประชาชน';

    #[RequiresPhpExtension('intl')]
    public function test_it_adds_only_invisible_safe_breaks_to_distributed_text_nodes(): void
    {
        $html = '<p class="thai-distributed">'.self::TEXT.' ติดต่อ 035-201-671 test@example.com example.pdf</p>'
            .'<p class="subject">เรื่อง ทดสอบการตัดคำภาษาไทย</p>';

        $result = app(ThaiLineBreaker::class)->processHtml($html);

        $this->assertStringContainsString("\u{200B}", $result);
        $this->assertStringNotContainsString("035-\u{200B}201", $result);
        $this->assertStringNotContainsString("test@\u{200B}example.com", $result);
        $this->assertStringNotContainsString("example.\u{200B}pdf", $result);
        $this->assertDoesNotMatchRegularExpression('/\x{200B}\p{M}/u', $result);
        $this->assertStringContainsString("วัตถุประสงค์\u{200B}เพื่อ", $result);
        $this->assertStringNotContainsString("วัตถุประสงค\u{200B}์", $result);
        $this->assertStringContainsString('<p class="subject">เรื่อง ทดสอบการตัดคำภาษาไทย</p>', $result);

        $withoutBreaks = str_replace("\u{200B}", '', strip_tags($result));
        $expected = self::TEXT.' ติดต่อ 035-201-671 test@example.com example.pdfเรื่อง ทดสอบการตัดคำภาษาไทย';
        $this->assertSame($expected, html_entity_decode($withoutBreaks, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public function test_it_returns_the_original_html_when_no_distributed_paragraph_exists(): void
    {
        $html = '<p>ข้อความภาษาไทยที่เป็นหัวเรื่อง</p>';

        $this->assertSame($html, app(ThaiLineBreaker::class)->processHtml($html));
    }
}
