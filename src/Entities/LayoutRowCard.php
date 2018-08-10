<?php

namespace Entities;

use Entities\Traits\TimestampTrait;

class LayoutRowCard extends Base
{
    use TimestampTrait;

    public $row_id;

    public $priority;

    public $size;

    public $type = 'image';

    public $image;

    public $product;

    public $product_row;

    public $link;

    private $cdnUrl = 'http://localhost/resources/app/layout';

    private $image_url;

    private $image_data;

    public $text_markdown;

    public $is_logged_in;

    public function __construct()
    {
        parent::__construct();
        if (getenv('FILESYSTEM') !== 'local') {
            $this->cdnUrl = 'https://storage.googleapis.com/adrcdn/layout';
        }

        if ($this->image !== null) {
            $this->image_url = $this->cdnUrl . '/' . $this->getImage();
        }
    }


    /**
     * @return mixed
     */
    public function getRowId()
    {
        return $this->row_id;
    }

    /**
     * @param mixed $row
     */
    public function setRowId($row)
    {
        $this->row_id = $row;
    }

    /**
     * @return mixed
     */
    public function getPriority()
    {
        return (int)$this->priority;
    }

    /**
     * @param mixed $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param mixed $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param mixed $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    public function getImageData()
    {
        if ($this->image_data === null) {
            if ($this->getImage() !== null) {
                $imageUrl = $this->cdnUrl . '/' . $this->getImage();
                $image = file_get_contents($imageUrl);
                $mime = $this->mapImageExifType($imageUrl);
                $this->image_data = 'data:' . $mime . ';base64,' . base64_encode($image);
            }
        }

        return $this->image_data;
    }

    /**
     * @return mixed
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param mixed $product
     */
    public function setProduct($product)
    {
        $this->product = $product;
    }

    /**
     * @return mixed
     */
    public function getProductRow()
    {
        if (is_null($this->product_row)) {
            return $this->product_row;
        }
        return json_decode($this->product_row);
    }

    /**
     * @param mixed $product_row
     */
    public function setProductRow($product_row)
    {
        $this->product_row = $product_row;
    }

    /**
     * @return mixed
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param mixed $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * @return mixed
     */
    public function getTextMarkdown()
    {
        if (is_null($this->text_markdown)) {
            return $this->text_markdown;
        }
        return json_decode($this->text_markdown);
    }

    /**
     * @param mixed $text_markdown
     */
    public function setTextMarkdown($text_markdown)
    {
        $this->text_markdown = $text_markdown;
    }

    /**
     * @return bool
     */
    public function getIsLoggedIn(): bool
    {
        if ($this->is_logged_in === 1) {
            return true;
        }

        return false;
    }

    /**
     * @param string|bool $is_logged_in
     */
    public function setIsLoggedIn($is_logged_in): void
    {
        if (in_array($is_logged_in, ['yes', true], true)) {
            $this->$is_logged_in = 1;
            return;
        }

        $this->$is_logged_in = 0;
    }

    private function mapImageExifType($url)
    {
        $imageType = exif_imagetype($url);
        $mime = '';
        switch ($imageType) {
            case 1:
                $mime = 'image/gif';
                break;
            case 2:
                $mime = 'image/jpeg';
                break;
            case 3:
                $mime = 'image/png';
        }
        return $mime;
    }
}
