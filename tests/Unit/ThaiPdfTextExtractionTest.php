<?php

namespace Tests\Unit;

use App\Services\Pdf\ThaiPdfFactory;
use Mpdf\HTMLParserMode;
use Mpdf\Output\Destination;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ThaiPdfTextExtractionTest extends TestCase
{
    private const TEXT = 'ด้วย ศูนย์ส่งเสริมการเรียนรู้ระดับอำเภอเสนา กำหนดจัดโครงการอาหารว่างสร้างรายได้ให้แก่ประชาชน ณ สกร.ระดับอำเภอเสนา หมู่ที่ 2 ตำบลวังสำโรง อำเภอบางมูลนาก ในระหว่างวันที่ 12 กรกฎาคม 2569 ถึง 12 กรกฎาคม 2569 ตั้งแต่เวลา 09:00 น. ถึง 15:00 น. จำนวนผู้เรียนตามบัญชีรายชื่อ โดยมีวัตถุประสงค์เพื่อพัฒนาทักษะด้านอาชีพให้แก่ประชาชน';

    public function test_thai_text_remains_searchable_and_copyable_after_pdf_rendering(): void
    {
        $finder = new ExecutableFinder;
        $pdftotext = $finder->find('pdftotext');
        $python = $finder->find('python3');
        if (! $pdftotext && ! $python) {
            $this->markTestSkipped('pdftotext or Python with pdfplumber is required for PDF text extraction QA.');
        }

        config()->set('pdf.thai_line_breaker', 'intl');
        $factory = app(ThaiPdfFactory::class);
        $mpdf = $factory->make();
        $mpdf->WriteHTML('.thai-distributed{text-align:justify;direction:ltr;line-height:1.08;text-indent:18mm;letter-spacing:normal;word-spacing:normal;white-space:normal}', HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($factory->prepareHtml('<p class="thai-distributed">'.self::TEXT.'</p>'), HTMLParserMode::HTML_BODY);
        $pdfPath = tempnam(sys_get_temp_dir(), 'sena-thai-').'.pdf';
        $textPath = $pdfPath.'.txt';

        try {
            file_put_contents($pdfPath, $mpdf->Output('', Destination::STRING_RETURN));
            $command = $pdftotext
                ? [$pdftotext, '-layout', $pdfPath, $textPath]
                : [$python, '-c', 'import pdfplumber,sys; p=pdfplumber.open(sys.argv[1]); open(sys.argv[2],"w",encoding="utf-8").write("\n".join((x.extract_text() or "") for x in p.pages))', $pdfPath, $textPath];
            $process = new Process($command);
            $process->mustRun();
            $extracted = (string) file_get_contents($textPath);
            $normalize = static fn (string $text): string => preg_replace('/[\s\x{200B}]+/u', '', $text) ?? '';

            $this->assertStringContainsString($normalize(self::TEXT), $normalize($extracted));
            $this->assertStringContainsString('09:00', $extracted);
            $this->assertStringContainsString('15:00', $extracted);
            $this->assertDoesNotMatchRegularExpression('/\x{25CC}/u', $extracted);
            $this->assertDoesNotMatchRegularExpression('/[\x{E000}-\x{F8FF}]/u', $extracted);
            $this->assertStringNotContainsString('□', $extracted);
        } finally {
            @unlink($pdfPath);
            @unlink($textPath);
        }
    }

    public function test_zero_width_thai_breaks_are_visually_empty_in_every_font_face(): void
    {
        $pdftoppm = (new ExecutableFinder)->find('pdftoppm');
        if (! $pdftoppm) {
            $this->markTestSkipped('pdftoppm is required for zero-width glyph raster QA.');
        }

        $factory = app(ThaiPdfFactory::class);
        $plain = '<p>กขภาษาไทย</p><p><b>กขภาษาไทย</b></p>'
            .'<p><i>กขภาษาไทย</i></p><p><b><i>กขภาษาไทย</i></b></p>';
        $withBreaks = str_replace('กข', "ก\u{200B}ข", $plain);
        $paths = [];

        try {
            foreach (['plain' => $plain, 'breaks' => $withBreaks] as $label => $html) {
                $mpdf = $factory->make();
                $mpdf->WriteHTML('body{font-family:thsarabunnew135zws;font-size:24pt;line-height:1.1}', HTMLParserMode::HEADER_CSS);
                $mpdf->WriteHTML($factory->prepareHtml($html), HTMLParserMode::HTML_BODY);
                $pdfPath = tempnam(sys_get_temp_dir(), 'sena-zws-'.$label.'-');
                $imagePrefix = $pdfPath.'-raster';
                file_put_contents($pdfPath, $mpdf->Output('', Destination::STRING_RETURN));

                (new Process([$pdftoppm, '-f', '1', '-singlefile', '-png', '-r', '144', $pdfPath, $imagePrefix]))
                    ->mustRun();

                $paths[$label] = $imagePrefix.'.png';
            }

            $this->assertSame(
                hash_file('sha256', $paths['plain']),
                hash_file('sha256', $paths['breaks']),
                'U+200B must add a line-break opportunity without drawing a stroke.'
            );
        } finally {
            foreach ($paths as $imagePath) {
                @unlink($imagePath);
                @unlink(str_replace('-raster.png', '', $imagePath));
            }
        }
    }
}
