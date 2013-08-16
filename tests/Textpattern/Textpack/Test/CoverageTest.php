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
     * Regular expression for finding unwanted characters.
     *
     * @var string
     */

    static private $regexInvisible = '/[\p{Cc}\p{Zp}\p{Zl}\x{00a0}\x{202f}\x{200b}\x{200B}]/u';

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
     * successfully, and that there were no
     * duplicate strings.
     */

    public function testDefaultTextpack()
    {
        $this->assertTrue(is_array(self::$defaultTextpack));
        $this->assertGreaterThan(1, count(self::$defaultTextpack));

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
            $lang = $file->getBasename('.textpack');

            $this->assertTrue(trim($contents) === $contents, "{$lang}: trailing whitespace");
            $this->assertTrue(mb_check_encoding($contents, 'UTF-8'), "{$lang}: not UTF-8");
            $this->assertTrue(strpos($contents, "\r") === false, "{$lang}: not using linefeed");
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
            $lang = $file->getBasename('.textpack');

            foreach ($strings as $data)
            {
                unset($missing[$data['name']]);
            }

            $count = count($missing);
            $missing = implode(', ', $missing);
            $this->assertEquals(0, $count, "{$lang}: missing {$count} strings: {$missing}");
        }
    }

    /**
     * Tests translation strings in Textpacks.
     */

    public function testStrings()
    {
        $exprectedCount = count(self::$defaultTextpack);

        foreach (self::$translations as $file)
        {
            $contents = file_get_contents($file->getPathname());
            $strings = self::$textpack->parse($contents);
            $lang = $file->getBasename('.textpack');

            // Make sure the file doesn't have extra strings.

            $this->assertEquals($exprectedCount, count($strings), "{$lang}: too many strings");

            foreach ($strings as $key => $data)
            {
                // Tests for widow strings.

                $this->assertTrue(
                    isset(self::$knownStrings[$data['name']]),
                    "{$lang}: {$data['name']} is not in en-GB"
                );

                $expected = self::$defaultTextpack[$key]['name'];

                $this->assertTrue(
                    self::$defaultTextpack[$key]['name'] === $data['name'] &&
                    self::$defaultTextpack[$key]['event'] === $data['event'] &&
                    self::$defaultTextpack[$key]['owner'] === $data['owner'],
                    "{$lang}: found {$data['name']}, expected {$expected}"
                );

                // Makes sure the string doesn't contain some unwanted invisible characters.

                $this->assertTrue(
                    !preg_match(self::$regexInvisible, $data['data']),
                    "{$lang}: {$data['name']} contains invisible characters"
                );

                // Makes sure the string doesn't go over the character limit.

                $length = mb_strlen($data['data']);

                $this->assertTrue(
                    65535 >= $length,
                    "{$lang}: {$data['name']} is {$length} characters long, 65535 allowed"
                );

                // Makes sure the string contains only allowed HTML elements.

                $strippedContent = strip_tags($data['data'], self::$allowedHTMLFormatting);

                $this->assertTrue(
                    $strippedContent === $data['data'],
                    "{$lang}: {$data['name']} contains illegal HTML formatting"
                );

                // Makes sure the string doesn't contain broken or unsanitised HTML.

                $strippedContent = strip_tags($strippedContent);

                $this->assertTrue(
                    htmlspecialchars($strippedContent, ENT_NOQUOTES, 'UTF-8', false) === $strippedContent,
                    "{$lang}: {$data['name']} contains unescaped HTML syntax characters"
                );

                if ($data['name'] === 'lang_dir')
                {
                    // Check language direction.
                    $this->assertTrue(
                        $data['data'] === 'ltr' || $data['data'] === 'rtl',
                        "{$lang}: invalid lang_dir"
                    );
                }
                else if ($data['name'] === 'lang_code')
                {
                    // Check language code.
                    $code = explode('-', $lang);
                    $code = $code[0].'-'.strtoupper(end($code));
                    $this->assertEquals($code, $data['data'], "{$lang}: invalid lang_code");
                }
            }
        }
    }
}