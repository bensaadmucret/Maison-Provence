<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a new admin user',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Check if admin already exists
        $existingAdmin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'contact.mzb@maison-lavande-provence.fr']);
        if ($existingAdmin) {
            $io->warning('An admin user already exists with this email.');
            return Command::INVALID;
        }

        $helper = $this->getHelper('question');
        
        // Ask for password
        $question = new Question('Please enter the admin password: ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $password = $helper->ask($input, $output, $question);

        if (empty($password)) {
            $io->error('Password cannot be empty');
            return Command::INVALID;
        }

        $user = new User();
        $user->setEmail('contact.mzb@maison-lavande-provence.fr');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setFirstName('Admin');
        $user->setLastName('User');

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $password
        );
        $user->setPassword($hashedPassword);

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $io->success('Admin user created successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred while creating the admin user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
