<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ProductSEO extends SEO
{
    #[ORM\OneToOne(mappedBy: 'seo', targetEntity: Product::class, cascade: ['persist'])]
    private ?Product $product = null;

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        if ($product === null && $this->product !== null) {
            $this->product->setSeo(null);
        }

        if ($product !== null && $product->getSeo() !== $this) {
            $product->setSeo($this);
        }

        $this->product = $product;

        return $this;
    }
}
