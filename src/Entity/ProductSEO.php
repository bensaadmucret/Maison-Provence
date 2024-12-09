<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ProductSEO extends SEO
{
    #[ORM\OneToOne(mappedBy: 'seo', targetEntity: Product::class)]
    private ?Product $product = null;

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        // Désassocier l'ancien produit si existant
        if (null !== $this->product && $this->product !== $product) {
            $oldProduct = $this->product;
            $this->product = null;
            $oldProduct->setSeo(null);
        }

        // Associer le nouveau produit
        $this->product = $product;
        if (null !== $product && $product->getSeo() !== $this) {
            $product->setSeo($this);
        }

        return $this;
    }
}
