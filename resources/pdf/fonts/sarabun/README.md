# Sarabun for Sena LPD PDFs

These files are based on the Sarabun family distributed by the official
Google Fonts repository under the SIL Open Font License 1.1.

PDF compatibility changes:

- `U+200B ZERO WIDTH SPACE` is mapped to a new empty glyph with advance width
  `0`, so mPDF can use invisible Thai word boundaries without drawing a box.
- `unitsPerEm` is set to `1450` so the existing official-document point sizes
  retain approximately the same visual scale and pagination as TH Sarabun New.

The font remains registered as the internal mPDF family `thsarabun` so the
existing document templates do not need to change. Unlike the legacy font,
its Unicode mark metrics render correctly in mPDF without detached vowels or
tone marks and without converting copied text to Private Use glyphs.

Source: https://github.com/google/fonts/tree/main/ofl/sarabun
