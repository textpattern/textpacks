<?php

namespace Textpattern\Textpack\Test;
use Textpattern\Textpack\Test\Parser as Textpack;

class CoverageTest extends \PHPUnit_Framework_TestCase
{
    private $defaultTextpack;
    private $textpack;
    private $translations;
    private $knownStrings = array();

    public function setUp()
    {
        $this->textpack = new Textpack();
        $contents = file_get_contents(__DIR__.'/../../../../textpacks/en-gb.textpack');
        $this->defaultTextpack = $this->textpack->parse($contents);
        $this->translations = new TextpackFilter(new \DirectoryIterator(__DIR__.'/../../../../textpacks'));

        foreach ($this->defaultTextpack as $data)
        {
            $this->knownStrings[$data['name']] = $data['name'];
        }
    }

    public function testDefaultTextpack()
    {
        $this->assertTrue(is_array($this->defaultTextpack));
        $this->assertGreaterThan(1, count($this->defaultTextpack));
    }

    public function testDuplicates()
    {
        $duplicate = $used = array();

        foreach ($this->defaultTextpack as $data)
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

    public function testUTF8Encoding()
    {
        foreach ($this->translations as $file)
        {
            $this->assertTrue(mb_check_encoding(file_get_contents($file->getPathname()), 'UTF-8'), $file->getBasename());
        }
    }

    public function testTrailingWhitespace()
    {
        foreach ($this->translations as $file)
        {
            $contents = file_get_contents($file->getPathname());
            $this->assertTrue(trim($contents) === $contents, $file->getBasename());
        }
    }

    public function testMisingStrings()
    {
        foreach ($this->translations as $file)
        {
            $contents = file_get_contents($file->getPathname());
            $strings = $this->textpack->parse($contents);
            $missing = $this->knownStrings;

            foreach ($strings as $data)
            {
                unset($missing[$data['name']]);
            }

            $this->assertEquals(0, count($missing), $file->getBasename().' is missing '.count($missing).' strings: '.implode(', ', $missing));
        }
    }

    public function testWidowStrings()
    {
        foreach ($this->translations as $file)
        {
            $contents = file_get_contents($file->getPathname());
            $strings = $this->textpack->parse($contents);

            foreach ($strings as $data)
            {
                $this->assertArrayHasKey($data['name'], $this->knownStrings, 'string '.$data['name'].' in '.$file->getBasename().' is not in en-gb');
            }
        }
    }

    public function testStringOrder()
    {
        foreach ($this->translations as $file)
        {
            $contents = file_get_contents($file->getPathname());
            $strings = $this->textpack->parse($contents);

            foreach ($strings as $key => $data)
            {
                $this->assertTrue(
                    $this->defaultTextpack[$key]['name'] === $data['name'] &&
                    $this->defaultTextpack[$key]['event'] === $data['event'] &&
                    $this->defaultTextpack[$key]['owner'] === $data['owner'],
                    'Index '.$key.' in '.$file->getBasename().' does not match en-gb: '.$data['name'].' vs. '.$this->defaultTextpack[$key]['name']
                );
            }
        }
    }
}