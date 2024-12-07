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
        if (null === $product && null !== $this->product) {
            $this->product->setSeo(null);
        }

        if (null !== $product && $product->getSeo() !== $this) {
            $product->setSeo($this);
        }

        $this->product = $product;

        return $this;
    }

    public function setOgImage(?string $ogImage): self
    {
        $openGraphData = $this->getOpenGraphData();
        $openGraphData['image'] = $ogImage;
        $this->setOpenGraphData($openGraphData);

        return $this;
    }
}
