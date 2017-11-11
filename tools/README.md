# Textpattern CMS language files - tools

This directory contains Textpack-related tools.

## `check-textpack.php`

This script dumps any language strings it finds defined in the code that are missing from the Textpack.

Copy `check-textpack.php`to your Textpattern root directory. From the command line, run:

```php
php check-textpack.php > ./missing.txt
```

Open `missing.txt` in a text editor for further information on the strings found, including any required replacement {parameters} inside the string where relevant. Additionally, the filename and line number that the first use of this string occurred is provided. The entire line where it was used is reproduced as the final item.

## License

Licensed under the [GPLv2 license](https://github.com/textpattern/textpacks/blob/master/LICENSE).
