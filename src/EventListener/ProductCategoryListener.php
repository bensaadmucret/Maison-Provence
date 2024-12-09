<?php

namespace App\EventListener;

use App\Entity\Product;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Product::class)]
class ProductCategoryListener
{
    public function prePersist(Product $product, PrePersistEventArgs $event): void
    {
        // Suppression de toute logique de catégorie par défaut
        // Les produits peuvent maintenant être créés sans catégorie
        return;
    }
}
