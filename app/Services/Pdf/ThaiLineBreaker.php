<?php

namespace App\Services\Pdf;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Facades\Log;
use IntlBreakIterator;
use Throwable;

final class ThaiLineBreaker
{
    private const ZERO_WIDTH_SPACE = "\u{200B}";

    private const PROTECTED_TOKEN_PATTERN = <<<'REGEX'
~((?:https?://|www\.)[^\s<]+|[\p{L}\p{N}._%+\-]+@[\p{L}\p{N}.\-]+\.[A-Z]{2,}|(?<![\p{L}\p{N}])\+?\d[\d\s().\-]{5,}\d(?![\p{L}\p{N}])|[\p{L}\p{N}_\-]+\.(?:pdf|docx?|xlsx?|pptx?|zip|rar|jpe?g|png))~iu
REGEX;

    public function isAvailable(): bool
    {
        return extension_loaded('intl') && class_exists(IntlBreakIterator::class);
    }

    /**
     * Add invisible break opportunities to text nodes inside .thai-distributed.
     * The source HTML and database values are never modified.
     */
    public function processHtml(string $html): string
    {
        if (! $this->isAvailable() || $html === '' || ! str_contains($html, 'thai-distributed')) {
            return $html;
        }

        if (! mb_check_encoding($html, 'UTF-8')) {
            Log::warning('Thai PDF line breaker skipped invalid UTF-8 HTML.');

            return $html;
        }

        try {
            return $this->processDom($html);
        } catch (Throwable $exception) {
            report($exception);

            return $html;
        }
    }

    private function processDom(string $html): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $wrapped = '<?xml encoding="UTF-8"><div id="thai-pdf-root">'.$html.'</div>';

        try {
            $loaded = $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            if (! $loaded) {
                return $html;
            }

            $xpath = new DOMXPath($dom);
            $query = '//*[@id="thai-pdf-root"]//*[contains(concat(" ", normalize-space(@class), " "), " thai-distributed ")]//text()'
                .'[not(ancestor::script) and not(ancestor::style) and not(ancestor::code) and not(ancestor::pre)]';
            $nodes = $xpath->query($query);

            if ($nodes === false) {
                return $html;
            }

            /** @var DOMNode $node */
            foreach ($nodes as $node) {
                $node->nodeValue = $this->processText((string) $node->nodeValue);
            }

            $root = $dom->getElementById('thai-pdf-root');
            if (! $root instanceof DOMElement) {
                return $html;
            }

            $result = '';
            foreach ($root->childNodes as $child) {
                $result .= $dom->saveHTML($child);
            }

            return $result;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
    }

    private function processText(string $text): string
    {
        if ($text === '' || ! preg_match('/\p{Thai}/u', $text)) {
            return $text;
        }

        $parts = preg_split(self::PROTECTED_TOKEN_PATTERN, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $text;
        }

        foreach ($parts as $index => $part) {
            if ($part === '' || preg_match(self::PROTECTED_TOKEN_PATTERN, $part) === 1) {
                continue;
            }

            $parts[$index] = $this->addBreakOpportunities($part);
        }

        return implode('', $parts);
    }

    private function addBreakOpportunities(string $text): string
    {
        $words = IntlBreakIterator::createWordInstance('th_TH');
        $characters = IntlBreakIterator::createCharacterInstance('th_TH');
        if (! $words || ! $characters) {
            return $text;
        }

        $words->setText($text);
        $characters->setText($text);
        $boundaries = [];
        $byteLength = strlen($text);

        foreach ($words as $offset) {
            if (! is_int($offset) || $offset <= 0 || $offset >= $byteLength) {
                continue;
            }

            if ($characters->isBoundary($offset) && $this->isSafeThaiBoundary($text, $offset)) {
                $boundaries[] = $offset;
            }
        }

        if ($boundaries === []) {
            return $text;
        }

        $result = '';
        $cursor = 0;
        foreach (array_values(array_unique($boundaries)) as $offset) {
            $result .= substr($text, $cursor, $offset - $cursor).self::ZERO_WIDTH_SPACE;
            $cursor = $offset;
        }

        return $result.substr($text, $cursor);
    }

    private function isSafeThaiBoundary(string $text, int $offset): bool
    {
        $left = substr($text, 0, $offset);
        $right = substr($text, $offset);
        if ($left === '' || $right === '' || str_ends_with($left, self::ZERO_WIDTH_SPACE) || str_starts_with($right, self::ZERO_WIDTH_SPACE)) {
            return false;
        }

        preg_match('/(\X)$/u', $left, $leftCluster);
        preg_match('/^(\X)/u', $right, $rightCluster);
        $before = $leftCluster[1] ?? '';
        $after = $rightCluster[1] ?? '';

        return $before !== '' && $after !== ''
            && preg_match('/\p{Thai}/u', $before) === 1
            && preg_match('/\p{Thai}/u', $after) === 1
            && preg_match('/^\p{M}/u', $after) !== 1;
    }
}
