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

    public function testIsADataImgUrlReturnsFalse()
    {
        $imageData = "data:imagesomestring";
        $isMatch = preg_match('/^data:image\/(\w+);base64,/', $imageData, $type);
        $this->assertEquals(0, $isMatch);
    }

    public function testIsADataImgUrlReturnsTrue()
    {
        $imageData = "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxIQERUQERISFRUWGBUXFhgVFRUWFhYVFxUYFhUYFxUYHSggGBolGxgXITEhJSkrLi4uGB8zODMsNygtLisBCgoKDg0OGxAQGislICYtLS0tLS0tLS0tLS0tLS0t";
        $isMatch = preg_match('/^data:image\/(\w+);base64,/', $imageData, $type);
        $this->assertEquals(1, $isMatch);
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
