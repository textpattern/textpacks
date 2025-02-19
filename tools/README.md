# Textpattern CMS language files - tools

This directory contains Textpack-related tools.

## `check-textpack.php`

This script dumps any language strings it finds defined in the code that are missing from the Textpack.

Copy `check-textpack.php`to your Textpattern root directory. From the command line, run:

```php
php check-textpack.php > ./missing.txt
```

Open `missing.txt` in a text editor for further information on the strings found, including any required replacement {parameters} inside the string where relevant. Additionally, the filename and line number that the first use of this string occurred is provided. The entire line where it was used is reproduced as the final item.

## `move-strings.php`

Allows strings to be moved freely from one group to another, across all language files at once.

A side benefit is that the strings are automatically reindexed to be in alphabetical order within each group.

You can run the script as-is or copy it to a particular Textpattern directory, and pass the `--dir=` parameter to point to the `lang` directory on which you wish to operate, relative to the script's location.

### Example 1

Reindex the language files in the textpack repository.

```php
php move-strings.php
```

### Example 2

Reindex the setup language files in the textpack repository.

```php
php move-strings.php --dir=../lang-setup
```

### Example 3

Move 'show' and 'meta' from the admin-side group, and 'css_name' from the css group, to the common group. Reindex the strings if necessary.

```php
php move-strings.php --keys=admin-side.show,admin-side.meta,css.css_name --to=common
```

### Example 4

Move all the pre-defined role strings from the admin group to the admin-side group, and reindex the strings.

```php
php move-strings.php --keys=publisher,managing_editor,staff_writer,copy_editor,designer,freelancer --from=admin --to=admin-side
```

## License

Licensed under the [GPLv2 license](https://github.com/textpattern/textpacks/blob/master/LICENSE).
