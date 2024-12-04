<?php

namespace App\Message;

class ProcessImageMessage
{
    public function __construct(
        private readonly int $imageId,
        private readonly array $dimensions,
        private readonly ?string $filter = null
    ) {}

    public function getImageId(): int
    {
        return $this->imageId;
    }

    public function getDimensions(): array
    {
        return $this->dimensions;
    }

    public function getFilter(): ?string
    {
        return $this->filter;
    }
}
