# TH Sarabun New for Sena LPD PDFs

These are the four official-style **TH Sarabun New 1.35 (2011)** faces by SIPA,
downloaded from:

- https://www.f0nt.com/release/th-sarabun-new/
- https://www.f0nt.com/download/sipafonts/THSarabunNew.zip

The release is distributed as GPL 2.0 with the font exception. The visible
character glyph outlines and metrics are unchanged from the downloaded files.

For reliable mPDF line wrapping, each font contains a dedicated empty glyph for
`U+200B ZERO WIDTH SPACE`. It has `0` contours, `0` points, and advance width
`0`. Do not map U+200B to TH Sarabun New's original `zerowidthnonjoiner` glyph:
despite its name, that legacy glyph contains a narrow vertical rectangle which
mPDF renders as a line between Thai words. The Intl Thai line breaker inserts
U+200B only at safe word boundaries; without a dedicated empty mapping mPDF
either renders those lines or missing-glyph boxes.

The application deliberately keeps mPDF's Thai OTL shaper and broad font
substitution disabled. Enabling either can expose Private Use glyphs when text
is copied from a PDF.

The application registers this family internally as `thsarabunnew135zws` so
mPDF cannot reuse stale metrics from the earlier broken mapping. No font
installation, runtime patching tool, or extra Composer package is required.
