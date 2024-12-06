<?php

namespace App\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SendTestMailCommand extends Command
{
    /**
     * @var MailerInterface
     */
    private MailerInterface $mailer;

    /**
     * @var string
     */
    private $sender;
    /**
     * @var string
     */
    private $pecSender;

    /**
     * @param MailerInterface $mailer
     * @param string $sender
     * @param string $pecSender
     */
    public function __construct(
        MailerInterface $mailer,
        string $sender,
        string $pecSender
    ) {
        parent::__construct();
        $this->mailer = $mailer;
        $this->sender = $sender;
        $this->pecSender = $pecSender;
    }

    protected function configure()
    {
        $this
            ->setName('app:send-test-email')
            ->addArgument('transport', InputArgument::OPTIONAL, 'The transport tu use for sending test mail')
            ->addArgument('email', InputArgument::OPTIONAL, 'The recipient email address');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $emailAddress = $input->getArgument('email');
        $transport = $input->getArgument('transport');

        if (!$emailAddress) {
            $helper = $this->getHelper('question');
            $question = new Question('Please enter the email address to send a test email to: ');
            $emailAddress = $helper->ask($input, $output, $question);
        }

        if (!$transport) {
            $helper = $this->getHelper('question');
            $question = new Question('Please enter the email transport to use (pec or mail): ');
            $transport = $helper->ask($input, $output, $question);
        }

        // Validate transport input
        if (!in_array($transport, ['pec', 'mail'])) {
            $output->writeln('<error>Invalid transport specified. Use "pec" or "mail".</error>');
            return Command::FAILURE;
        }

        $sender = $transport === 'pec' ?
            $this->pecSender :
            $this->sender;

        $email = (new Email())
            ->from($sender)
            ->to($emailAddress)
            ->subject('[TEST]')
            ->text('This is a test email sent by the console command using the ' . $transport . ' transport.');

        if ($transport === 'pec') {
            $email->getHeaders()->addTextHeader('X-Transport', 'pec');
            $output->writeln('PEC activated');
        }

        try {
            $this->mailer->send($email);
            $output->writeln('Test email sent to ' . $emailAddress . ' using ' . $transport . ' transport.');
        } catch (Exception $e) {
            $output->writeln('Unable to send message due to an exception: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
