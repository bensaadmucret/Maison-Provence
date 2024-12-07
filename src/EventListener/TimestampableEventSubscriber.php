<?php

namespace App\EventListener;

use App\Entity\LegalPage;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class TimestampableEventSubscriber implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($this->isTimestampable($entity)) {
            $now = new \DateTimeImmutable();

            if (method_exists($entity, 'setCreatedAt')) {
                $entity->setCreatedAt($now);
            }

            if (method_exists($entity, 'setUpdatedAt')) {
                $entity->setUpdatedAt($now);
            }
        }
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($this->isTimestampable($entity) && method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt(new \DateTimeImmutable());
        }
    }

    private function isTimestampable(object $entity): bool
    {
        return $entity instanceof LegalPage
               || $entity instanceof Product;
    }
}
