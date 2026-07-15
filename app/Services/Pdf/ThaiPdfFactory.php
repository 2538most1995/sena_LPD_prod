<?php

namespace App\Services\Pdf;

use Illuminate\Support\Facades\File;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

final class ThaiPdfFactory
{
    public function __construct(private readonly ThaiLineBreaker $lineBreaker) {}

    public function make(bool $landscape = false): Mpdf
    {
        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontConfig = (new FontVariables)->getDefaults();
        $tempDirectory = storage_path('app/mpdf');
        File::ensureDirectoryExists($tempDirectory, 0775, true);
        $useIntlFallback = $this->usesIntlFallback();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => $landscape ? 'A4-L' : 'A4',
            'margin_left' => $landscape ? 20 : 30,
            'margin_right' => 20,
            'margin_top' => 15,
            'margin_bottom' => $landscape ? 15 : 20,
            'fontDir' => array_merge($defaultConfig['fontDir'], [resource_path('pdf/fonts/thsarabun')]),
            'fontdata' => $fontConfig['fontdata'] + [
                'thsarabunnew135zws' => [
                    'R' => 'THSarabunNew.ttf',
                    'B' => 'THSarabunNew-Bold.ttf',
                    'I' => 'THSarabunNew-Italic.ttf',
                    'BI' => 'THSarabunNew-BoldItalic.ttf',
                    // TH Sarabun New's native mark metrics render correctly
                    // without mPDF's Thai shaper. Enabling it introduces
                    // Private Use glyphs into copied/extracted PDF text.
                    'useOTL' => 0,
                ],
            ],
            'default_font' => 'thsarabunnew135zws',
            'tempDir' => $tempDirectory,
            'useDictionaryLBR' => ! $useIntlFallback,
        ]);

        $justification = config('pdf.justification', []);
        $mpdf->jSWord = $this->boundedFloat($justification['word_ratio'] ?? 0.15, 0, 1);
        $mpdf->jSmaxChar = $this->boundedFloat($justification['max_character_spacing'] ?? 0.55, 0.05, 2);
        $mpdf->jSmaxCharLast = $this->boundedFloat($justification['max_last_line_character_spacing'] ?? 0, 0, 2);
        $mpdf->jSmaxWordLast = $this->boundedFloat($justification['max_last_line_word_spacing'] ?? 0, 0, 4);
        $mpdf->justifyB4br = (bool) ($justification['justify_before_br'] ?? false);
        $mpdf->useDictionaryLBR = ! $useIntlFallback;
        // The bundled TH Sarabun New files map U+200B to a dedicated empty,
        // zero-advance glyph. The font's legacy zerowidthnonjoiner is not
        // actually empty and appears as a vertical stroke in mPDF output.
        // Broad substitution is unnecessary and can also make copied text
        // differ from the source.
        $mpdf->useSubstitutions = false;
        $mpdf->showImageErrors = true;

        return $mpdf;
    }

    public function prepareHtml(string $body): string
    {
        if ($this->usesIntlFallback()) {
            $body = $this->lineBreaker->processHtml($body);
        }

        return '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"></head><body lang="th">'.$body.'</body></html>';
    }

    public function usesIntlFallback(): bool
    {
        return config('pdf.thai_line_breaker', 'intl') === 'intl'
            && $this->lineBreaker->isAvailable();
    }

    private function boundedFloat(mixed $value, float $minimum, float $maximum): float
    {
        return min($maximum, max($minimum, (float) $value));
    }
}
