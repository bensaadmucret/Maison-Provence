<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a new super admin user',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', 'm', InputOption::VALUE_REQUIRED, 'Email address for the admin user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get or ask for email
        $email = $input->getOption('email');
        if (!$email) {
            $email = $io->ask('Please enter the admin email', 'contact.mzb@maison-lavande-provence.fr');
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Invalid email address');

            return Command::INVALID;
        }

        // Check if admin already exists
        $existingAdmin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingAdmin) {
            $io->warning('An admin user already exists with this email.');

            return Command::INVALID;
        }

        // Ask for password
        $question = new Question('Please enter the admin password: ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $password = $this->getHelper('question')->ask($input, $output, $question);

        if (empty($password)) {
            $io->error('Password cannot be empty');

            return Command::INVALID;
        }

        // Show summary before creation
        $io->section('Summary of the admin to be created:');
        $io->table(
            ['Setting', 'Value'],
            [
                ['Email', $email],
                ['Role', 'SUPER ADMIN'],
            ]
        );

        if (!$io->confirm('Do you want to create this super admin user?', true)) {
            $io->warning('Operation cancelled');

            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_SUPER_ADMIN']);
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
            $io->success('Super admin user created successfully.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred while creating the super admin user: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
