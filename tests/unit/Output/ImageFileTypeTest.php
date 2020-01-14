<?php

use PHPUnit\Framework\TestCase;

class ImageFileTypeTest extends TestCase
{
    public function testImageFileTypeIsValidReturnsFalse()
    {
        $type = $this->badFileType();

        $this->assertFalse(in_array($type, ['jpg', 'jpeg', 'gif', 'png']));
    }

    public function testImageFileTypeIsValidReturnsTrue()
    {
        $type = $this->goodFileType();

        $this->assertTrue(in_array($type, ['jpg', 'jpeg', 'gif', 'png']));
    }

    private function badFileType()
    {
        return $this->getImageType('someimage.Array');
    }

    private function goodFileType()
    {
        return $this->getImageType('someimage.png');
    }

    private function getImageType(string $fileName)
    {
        $imageFile = explode('.', $fileName);
        return $imageFile[1];
    }
}
