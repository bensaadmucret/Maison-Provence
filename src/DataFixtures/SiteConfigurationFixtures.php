<?php

namespace App\DataFixtures;

use App\Entity\SiteConfiguration;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SiteConfigurationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $config = new SiteConfiguration();
        $config->setSiteName('Maison Provence');
        $config->setMaintenanceMode(false);
        $config->setMaintenanceMessage('Le site est actuellement en maintenance. Nous serons bientôt de retour.');
        $config->setContactEmail('contact@maison-lavande-provence.fr');
        $config->setIsEcommerceEnabled(true);
        $config->setIsEcommerceEnabledNew(false);
        $config->setEcommerceDisabledMessage('La boutique en ligne est temporairement désactivée.');

        $manager->persist($config);
        $manager->flush();
    }
}
