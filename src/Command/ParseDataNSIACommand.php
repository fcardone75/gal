<?php

namespace App\Command;

use App\Service\Nsia\CommunicationNSIA;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ParseDataNSIACommand
 */
class ParseDataNSIACommand extends Command
{
    /**
     * @var CommunicationNSIA
     */
    private $communicationNSIA;

    public function __construct(CommunicationNSIA $communicationNSIA, ?string $name = null)
    {
        $this->communicationNSIA = $communicationNSIA;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('app:nsia:parse')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$output->writeln('COMMAND app:nsia:parse - START');

        $this->communicationNSIA->parseDataFilesNSIA($output);

		$output->writeln('COMMAND app:nsia:parse - END');
//TODO: gestione errore?
        return Command::SUCCESS;
    }
}
