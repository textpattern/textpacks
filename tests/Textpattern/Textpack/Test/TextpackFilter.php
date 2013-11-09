<?php

namespace Textpattern\Textpack\Test;

class TextpackFilter extends \FilterIterator
{
    public function accept()
    {
         return $this->current()->getExtension() === 'textpack';
    }
}
