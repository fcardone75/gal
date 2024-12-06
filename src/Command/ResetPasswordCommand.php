<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
class ResetPasswordCommand extends Command
{
    protected static $defaultName = 'app:reset-password';

    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Imposta una password casuale per un utente dato')
            ->addArgument('email', InputArgument::REQUIRED, "L'email dell'utente");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            throw new UserNotFoundException(sprintf('Utente con email "%s" non trovato', $email));
        }

        $randomPassword = bin2hex(random_bytes(10)); // Genera una password casuale
        $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->flush();

        // Stampa la password generata a video
        $output->writeln(sprintf('Password casuale impostata per l\'utente con email "%s".', $email));
        $output->writeln(sprintf('La nuova password è: %s', $randomPassword));

        return Command::SUCCESS;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (!$input->getArgument('email')) {
            $email = $this->getHelper('question')->ask($input, $output, new Question('Inserisci la user email: '));
            $input->setArgument('email', $email);
        }
    }
}
