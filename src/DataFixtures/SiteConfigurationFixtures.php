<?php

namespace App\DataFixtures;

use App\Entity\SiteConfiguration;

class SiteConfigurationFixtures extends BaseFixture
{
    protected function loadData(): void
    {
        $config = new SiteConfiguration();
        $config->setIsEcommerceEnabled($this->faker->boolean(20)) // 20% chance of being enabled
              ->setEcommerceDisabledMessage($this->faker->sentence());

        $this->manager->persist($config);
        $this->addReference('site-config', $config);
    }
}
