<?php


namespace App\Command;


use App\Entity\Confidi;
use App\Entity\User;
use App\Service\Contracts\Security\RoleProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateUserCommand extends Command
{
    protected static $defaultName = 'app:create-user';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ValidatorInterface */
    private $validator;

    /** @var PasswordHasherFactoryInterface */
    private PasswordHasherFactoryInterface $passwordHasherFactory;

    /** @var RoleProviderInterface */
    private $roleProvider;

    /**
     * CreateUserCommand constructor.
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     * @param PasswordHasherFactoryInterface $passwordHasherFactory
     * @param string|null $name
     */
    public function __construct(
        EntityManagerInterface         $entityManager,
        ValidatorInterface             $validator,
        PasswordHasherFactoryInterface $passwordHasherFactory,
        RoleProviderInterface          $roleProvider,
        string                         $name = null
    ) {
        parent::__construct($name);
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->passwordHasherFactory = $passwordHasherFactory;
        $this->roleProvider = $roleProvider;
    }

    protected function configure()
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'User email')
            ->addArgument('password', InputArgument::OPTIONAL, 'User password')
            ->addArgument('roles', InputArgument::OPTIONAL, 'User roles')
            ->addArgument('firstname', InputArgument::OPTIONAL, 'User First Name')
            ->addArgument('lastname', InputArgument::OPTIONAL, 'User Last Name')
            ->addArgument('confidi', InputArgument::OPTIONAL, 'User Associated Confidi')
            ->addOption('enabled', 'a',InputOption::VALUE_OPTIONAL, 'Enable/disable user (default true -> enabled)', true)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = new User();
        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($user);

        $emphasis = new OutputFormatterStyle(null, null, ['bold', 'underscore']);
        $output->getFormatter()->setStyle('emphasis', $emphasis);

        $email = $this->getArgumentInteractively(
            $input,
            $output,
            'email',
            'Please enter user\'s email: ',
            true
        );

        $password = $this->getArgumentInteractively(
            $input,
            $output,
            'password',
            'Please enter user\'s password: ',
            true,
            true
        );

        $roles = $this->selectRoles(
            $input,
            $output,
            'roles',
            'Please enter user\'s roles (optional - multiple values are allowed, comma separated): '
        );

        $firstName = $this->getArgumentInteractively(
            $input,
            $output,
            'firstname',
            'Please enter user\'s First Name (optional): '
        );

        $lastName = $this->getArgumentInteractively(
            $input,
            $output,
            'lastname',
            'Please enter user\'s Last Name (optional): '
        );

        $confidi = $this->selectConfidi(
            $input,
            $output,
            'confidi',
            'Please enter user\'s associated Confidi (optional)'
        );

        $user
            ->setEmail($email)
            ->setPlainPassword($password)
            ->setRoles($roles)
            ->setFirstname($firstName)
            ->setLastname($lastName)
            ->setConfidi($confidi)
            ->setPassword($passwordHasher->hash($password, $user->getSalt()))
            ->setPasswordIsGenerated(true);

        if (!$invalid = $this->validator->validate($user)) {
            throw new \Exception($invalid->get(0)->getMessage());
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return Command::SUCCESS;
    }

    protected function getArgumentInteractively(
        InputInterface $input,
        OutputInterface $output,
        string $argumentName,
        string $questionText,
        bool $isMandatory = false,
        bool $hidden = false
    ) {
        $helper = $this->getHelper('question');
        $argumentValue = $input->getArgument($argumentName);

        if (!$argumentValue) {
            $question = new Question($questionText);
            $question
                ->setHidden($hidden)
                ->setHiddenFallback(!$hidden);
            $argumentValue = $helper->ask($input, $output, $question);
            if (!$argumentValue && $isMandatory) {
                $argumentValue = $this->getArgumentInteractively($input, $output, $argumentName, $questionText, $isMandatory, $hidden);
            }
            if ($argumentName === 'email') {
                $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $argumentValue]);
                if ($existing) {
                    $output->writeln(sprintf('<comment>A user with email <emphasis>%s</emphasis> already exists. Please, specify a different email address</comment>', $argumentValue));
                    $argumentValue = $this->getArgumentInteractively($input, $output, $argumentName, $questionText, $isMandatory, $hidden);
                }
            }
        }

        return $argumentValue;
    }

    protected function selectRoles(
        InputInterface $input,
        OutputInterface $output,
        string $argumentName,
        string $questionText
    ) {
        $helper = $this->getHelper('question');
        $argumentValue = $input->getArgument($argumentName);

        if (!$argumentValue) {
            $choices = $this->roleProvider->getAssignableRoles();

            if ($choices) {
                $question = new ChoiceQuestion(
                    $questionText,
                    $choices
                );

                $question->setMultiselect(true);

                $argumentValue = $helper->ask($input, $output, $question);
            }
        }

        return $argumentValue;
    }

    protected function selectConfidi(
        InputInterface $input,
        OutputInterface $output,
        string $argumentName,
        string $questionText
    ) {
        $helper = $this->getHelper('question');
        $argumentValue = $input->getArgument($argumentName);

        if (!$argumentValue) {
            $confidiItems = $this->entityManager->getRepository(Confidi::class)->findAll();
            $choices = [];
            foreach ($confidiItems as $item) {
                $choices[$item->getId()] = implode(' | ', [ $item->getNsiaCode(), $item->getBusinessName()]);
            }
            if ($choices) {
                $question = new ChoiceQuestion(
                    $questionText,
                    $choices
                );

                $argumentValue = $helper->ask($input, $output, $question);

                if ($argumentValue) {
                    list($nsiaCode, $businessName) = explode(' | ', $argumentValue);
                    $argumentValue = $this->entityManager->getRepository(Confidi::class)->findOneBy(compact($nsiaCode, $businessName));
                }
            }
        }

        return $argumentValue;
    }
}
