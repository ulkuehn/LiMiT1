# About the contents of this directory

This directory is part of the LiMiT1 project (see <https://github.com/ulkuehn/LiMiT1>).

## CSS

The css files are replacement styles for the standard Bootstrap CSS, providing different skins for Bootstrap. All files taken from <https://bootswatch.com/>.

While the original files import the needed fonts online from Google Fonts (e.g. 
`@import url("https://fonts.googleapis.com/css?family=Ubuntu:400,700");`), they are rewritten to adapt for offline fonts by respective `@font` lines.

## Fonts

All font files are taken from Google Fonts to provide for offline fonts. This is done by the marvelous tool on <https://github.com/neverpanic/google-font-download>. That neat little script takes care for all downloading and for providing `@font` instructions that can directly be inserted into the Bootswatch style files, replacing the original `@import`line.

## Step by step procedure

To add a new skin:

1. Download css file from Bootswatch (bootstrap.min.css)
2. Rename properly
3. Comment font import line in css file
4. Use google-font-download to download fonts (e.g. `google-font-download -l latin-ext "Ubuntu:400" "Ubuntu:700"`)
5. Move downloaded font files to this directory
6. Copy contents of font.css into css file from Bootswatch after original import line
7. Skin is ready to use