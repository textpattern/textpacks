<?php

namespace Textpattern\Textpack\Test;
use Textpattern\Textpack\Test\Parser as Textpack;

class CoverageTest extends \PHPUnit_Framework_TestCase
{
    static private $defaultTextpack;
    static private $textpack;
    static private $translations;
    static private $knownStrings = array();
    static private $setUp = false;

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

    public function testDefaultTextpack()
    {
        $this->assertTrue(is_array(self::$defaultTextpack));
        $this->assertGreaterThan(1, count(self::$defaultTextpack));
    }

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

    public function testShallowLooks()
    {
        foreach (self::$translations as $file)
        {
            $contents = file_get_contents($file->getPathname());

            $this->assertTrue(trim($contents) === $contents, $file->getBasename().' has trailing whitespace');
            $this->assertTrue(mb_check_encoding($contents, 'UTF-8'), $file->getBasename().' is not UTF8');
            $this->assertTrue(strpos($contents, "\r") === false, $file->getBasename().' does not use single LF');
        }
    }

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

    public function testWidowStrings()
    {
        foreach (self::$translations as $file)
        {
            $contents = file_get_contents($file->getPathname());
            $strings = self::$textpack->parse($contents);

            foreach ($strings as $data)
            {
                $this->assertArrayHasKey($data['name'], self::$knownStrings, 'string '.$data['name'].' in '.$file->getBasename().' is not in en-gb');
            }
        }
    }

    public function testStrings()
    {
        foreach (self::$translations as $file)
        {
            $contents = file_get_contents($file->getPathname());
            $strings = self::$textpack->parse($contents);

            foreach ($strings as $key => $data)
            {
                $this->assertTrue(
                    self::$defaultTextpack[$key]['name'] === $data['name'] &&
                    self::$defaultTextpack[$key]['event'] === $data['event'] &&
                    self::$defaultTextpack[$key]['owner'] === $data['owner'],
                    'Index '.$key.' in '.$file->getBasename().' does not match en-gb: '.$data['name'].' vs. '.self::$defaultTextpack[$key]['name']
                );

                if ($data['name'] === 'lang_dir')
                {
                    $this->assertTrue($data['data'] === 'ltr' || $data['data'] === 'rtl', 'lang_dir in '.$file->getBasename());
                }
                else if ($data['name'] === 'lang_code')
                {
                    $this->assertEquals($file->getBasename('.textpack'), $data['data'], 'lang_code in '.$file->getBasename());
                }
            }
        }
    }
}