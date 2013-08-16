<?php

namespace Textpattern\Textpack\Test;
use Textpattern\Textpack\Test\Parser as Textpack;

/**
 * Tests Textpacks.
 */

class CoverageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Parsed contents of the main en-GB Textpack.
     *
     * @var array
     */

    static private $defaultTextpack;

    /**
     * Textpack parser instance.
     *
     * @var Textpattern\Textpack\Test\Parser
     */

    static private $textpack;

    /**
     * Translation files.
     *
     * @var \DirectoryIterator
     */

    static private $translations;

    /**
     * An array of accepted translation strings.
     *
     * Based on en-GB.
     *
     * @var array
     */

    static private $knownStrings = array();

    /**
     * Whether we have already initialised required objects.
     *
     * @var bool
     */

    static private $setUp = false;

    /**
     * List of accepted HTML elements.
     *
     * @var string
     */

    static private $allowedHTMLFormatting = '<a><abbr><b><bdo><cite><code><del><dfn><em><i><ins><kbd><q><samp><small><span><strong><sub><sup><var>';

    /**
     * {@inheritdoc}
     */

    public function setUp()
    {
        if (self::$setUp === false)
        {
            self::$textpack = new Textpack();
            $contents = file_get_contents(__DIR__.'/../../../../textpacks/en-gb.textpack');
            self::$defaultTextpack = self::$textpack->parse($contents);
            self::$translations = new TextpackFilter(new \DirectoryIterator(__DIR__.'/../../../../textpacks'));

            foreach (self::$defaultTextpack as $data)
            {
                self::$knownStrings[$data['name']] = $data['name'];
            }

            self::$setUp = true;
        }
    }

    /**
     * Tests the default Textpack.
     *
     * Makes sure the en-GB was parsed
     * successfully.
     */

    public function testDefaultTextpack()
    {
        $this->assertTrue(is_array(self::$defaultTextpack));
        $this->assertGreaterThan(1, count(self::$defaultTextpack));
    }

    /**
     * Makes sure the main Textpack doesn't contain duplicates.
     *
     * Since we are comparing other Textpacks to the en-GB,
     * this alone makes sure none of them contain
     * duplicates.
     */

    public function testDuplicates()
    {
        $duplicate = $used = array();

        foreach (self::$defaultTextpack as $data)
        {
            if (in_array($data['name'], $used))
            {
                $duplicate[] = $used;
            }
            else
            {
                $used[] = $data['name'];
            }
        }

        $this->assertEmpty($duplicate, 'duplicate strings: '.implode(', ', $duplicate));
    }

    /**
     * Tests basic file formatting.
     *
     * Checks trailing whitespace, line break style and
     * encoding.
     */

    public function testShallowLooks()
    {
        foreach (self::$translations as $file)
        {
            $contents = file_get_contents($file->getPathname());

            $this->assertTrue(trim($contents) === $contents, $file->getBasename().' has trailing whitespace');
            $this->assertTrue(mb_check_encoding($contents, 'UTF-8'), $file->getBasename().' is not UTF8');
            $this->assertTrue(strpos($contents, "\r") === false, $file->getBasename().' does not use single linefeed');
        }
    }

    /**
     * Tests Textpacks for missing strings.
     */

    public function testMisingStrings()
    {
        foreach (self::$translations as $file)
        {
            $contents = file_get_contents($file->getPathname());
            $strings = self::$textpack->parse($contents);
            $missing = self::$knownStrings;

            foreach ($strings as $data)
            {
                unset($missing[$data['name']]);
            }

            $this->assertEquals(0, count($missing), $file->getBasename().' is missing '.count($missing).' strings: '.implode(', ', $missing));
        }
    }

    /**
     * Tests translation strings in Textpacks.
     */

    public function testStrings()
    {
        foreach (self::$translations as $file)
        {
            $contents = file_get_contents($file->getPathname());
            $strings = self::$textpack->parse($contents);

            foreach ($strings as $key => $data)
            {
                // Tests for widow strings.

                $this->assertTrue(isset(self::$knownStrings[$data['name']]), 'string '.$data['name'].' in '.$file->getBasename().' is not in en-gb');

                // Makes sure strings are in correct order.

                $this->assertTrue(
                    self::$defaultTextpack[$key]['name'] === $data['name'] &&
                    self::$defaultTextpack[$key]['event'] === $data['event'] &&
                    self::$defaultTextpack[$key]['owner'] === $data['owner'],
                    'Index '.$key.' in '.$file->getBasename().' does not match en-gb: '.$data['name'].' vs. '.self::$defaultTextpack[$key]['name']
                );

                // Makes sure the string doesn't go over the character limit.

                $length = mb_strlen($data['data']);

                $this->assertTrue(65535 >= $length, 'String '.$data['name'].' in '.$file->getBasename().' is '.$length.' characters long, 65535 allowed.');

                // Makes sure the string contains only allowed HTML elements.

                $strippedContent = strip_tags($data['data'], self::$allowedHTMLFormatting);

                $this->assertTrue($strippedContent === $data['data'], 'String '.$data['name'].' in '.$file->getBasename().' contains illegal HTML formatting. Only few inline tags are allowed.');

                // Makes sure the string doesn't contain broken or unsanitised HTML.

                $strippedContent = strip_tags($strippedContent);

                $this->assertTrue(htmlspecialchars($strippedContent, ENT_NOQUOTES, 'UTF-8', false) === $strippedContent, 'String '.$data['name'].' in '.$file->getBasename().' contains unescaped HTML syntax characters');

                if ($data['name'] === 'lang_dir')
                {
                    // Check language direction.
                    $this->assertTrue($data['data'] === 'ltr' || $data['data'] === 'rtl', 'lang_dir in '.$file->getBasename());
                }
                else if ($data['name'] === 'lang_code')
                {
                    // Check language code.
                    $lang = explode('-', $file->getBasename('.textpack'));
                    $code = $lang[0].'-'.strtoupper(end($lang));
                    $this->assertEquals($code, $data['data'], 'lang_code in '.$file->getBasename());
                }
            }
        }
    }
}