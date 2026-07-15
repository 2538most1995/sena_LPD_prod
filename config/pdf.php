<?php

return [
    /*
    | mPDF can break Thai with its bundled dictionary when OTL is enabled.
    | Set PDF_THAI_LINE_BREAKER=intl to use the safe U+200B fallback instead.
    */
    'thai_line_breaker' => env('PDF_THAI_LINE_BREAKER', 'intl'),

    'justification' => [
        // Allocate most expansion to Thai character clusters, not visible spaces.
        'word_ratio' => (float) env('PDF_THAI_JS_WORD', 0.15),
        // Keep inter-cluster expansion subtle for 16 pt TH Sarabun New.
        'max_character_spacing' => (float) env('PDF_THAI_JS_MAX_CHAR', 0.55),
        // A final line must remain ragged unless an intentional <br> is justified.
        'max_last_line_character_spacing' => (float) env('PDF_THAI_JS_MAX_CHAR_LAST', 0),
        'max_last_line_word_spacing' => (float) env('PDF_THAI_JS_MAX_WORD_LAST', 0),
        'justify_before_br' => filter_var(env('PDF_THAI_JUSTIFY_BEFORE_BR', false), FILTER_VALIDATE_BOOL),
    ],
];
