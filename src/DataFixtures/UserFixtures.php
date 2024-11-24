<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@maison-provence.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setFirstName('Admin');
        $admin->setLastName('System');
        
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'admin'  // Default password, should be changed in production
        );
        $admin->setPassword($hashedPassword);
        
        $manager->persist($admin);
        $manager->flush();
    }
}
