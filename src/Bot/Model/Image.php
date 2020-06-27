<?php
declare(strict_types=1);
namespace Bot\Model;

class Image
{
    public $url;
    public $caption;

    public function __construct(string $url, string $caption = '')
    {
        $this->url = $url;
        $this->caption = $caption;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }
}