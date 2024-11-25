<?php

namespace App\Service;

use Symfony\Component\String\Slugger\SluggerInterface;

class SlugService
{
    public function __construct(
        private readonly SluggerInterface $slugger
    ) {
    }

    public function generateSlug(string $text): string
    {
        return strtolower($this->slugger->slug($text)->toString());
    }
}
