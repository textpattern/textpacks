# Textpattern CMS language files

[![Build Status](https://img.shields.io/travis/textpattern/textpacks/master.svg)](https://travis-ci.org/textpattern/textpacks)

This repository contains Textpacks for [Textpattern CMS](https://textpattern.com). Textpacks are text files containing translated strings, providing internationisation support for Textpattern.

## Contributing via Crowdin (preferred method)

Every translated string makes Textpattern better! The easiest way to get involved with translating the project is to use [Crowdin](http://translate.textpattern.io/). The tools they provide make translating Textpattern as easy as possible.

## Contributing via GitHub

To make corrections to existing translations, or to add new ones, [fork the repository](https://help.github.com/articles/fork-a-repo), make your changes and commit to your fork. Once you are done, open [a pull request](https://help.github.com/articles/using-pull-requests). To boost workflow the repository contains synchronisation scripts and automated tests.

### Contributing via GitHub web editor

GitHub's [web based editing features](https://help.github.com/articles/creating-and-editing-files-in-your-repository) allow you to make minor edits to existing translations without the need to delve into git. Changing files using the GitHub web interface isn't recommended, but it's an option for those that do not use git, or are away from their computer.

To contribute to a translation directly from GitHub:

1. [Sign in](https://github.com/login) with your GitHub account
2. Navigate to a Textpack file you want to alter, e.g. [textpacks/en-gb.txt](https://github.com/textpattern/textpacks/blob/master/textpacks/en-gb.txt).
3. Select the **Edit** button above the presented file contents.
4. Make some alterations to existing translations. Please don't remove or add any string.
5. When you are done, fill in the short commit message describing the change, e.g. *"Fixed typo in the Spanish translation for `change_email_address`"*.
6. Click or tap the **Save** button.

To submit in a new pull request with your changes:

1. Select the **Click to create a pull request for this comparison** link on the page presented after saving, or use the **Compare and pull request** button on your fork's repository page.
2. Fill in the comment field; explain what your changes do. Be precise and clear.
3. Select the **Send pull request** button.

Once your pull request is processed and marked closed (merged or denied), your fork of the Textpacks repository can be safely deleted.

To delete your local forked repository:

1. Find the 'textpacks' repository on your profile page; it should be listed on your repository list.
2. Open it.
3. On the repository's page, select the **Settings** link.
4. Scroll down until you see **Delete repository** button.
5. Click or tap it and confirm the action.

## Developing

### Adding and removing strings

Adding and removing strings happens through the main translation file, `en.txt`. Open up the file and add or remove strings as needed (remember to place new strings where they belong). When you are done, run the sync tool:

```ShellSession
$ ./textpack
```

The Textpack tool will sync your alterations to all other Textpack files. It creates new empty strings and removes anything that is no longer present in the root translation.

### Creating new translation

When creating entirely new translations, always use the `en.txt` as your template and reference point - this file is always up-to-date.

### Creating new empty template

Start by creating a new empty Textpack file:

```ShellSession
$ touch textpacks/xx-xx.txt
```

Then run the Textpack sync tool:

```ShellSession
$ ./textpack
```

This will populate your empty `xx-xx.txt` file with an empty Textpack template.

### Updating an existing translation

After running the sync tool, any new strings will be empty - you have to compare it with `en.txt` in order to review and update it, which can prove laborious. The tip below will help you translate new strings. **Note:** Commit everything before you do it!

First, filter all new strings:

```ShellSession
$ grep " =>[[:blank:]]*$" xx-xx.txt | \
xargs -n1 printf "grep '^%s =>' en.txt\n" | \
bash > xx-xx.txt
```

Then, delete untranslated lines:

```ShellSession
$ sed -i '/ =>[[:blank:]]*$/d' xx-xx.txt
```

This will leave you with just the new/empty strings. Edit `xx-xx.txt` in your favourite editor, then append the strings at the end of the original `xx-xx.txt`. Finally, run the sync tool and test:

```ShellSession
$ ./textpack
$ ./vendor/bin/phpunit
```

### Testing

The project uses [PHPunit](http://phpunit.de) for running its unit tests. Before running the tests, make sure you have installed dev-requirements using [Composer](http://getcomposer.org):

```ShellSession
$ composer install
```

To run a test:

```ShellSession
$ ./vendor/bin/phpunit
```

## License

Licensed under the [GPLv2 license](https://github.com/textpattern/textpacks/blob/master/LICENSE).
