<?php

namespace App\Command;

use App\Service\Nsia\CommunicationNSIA;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputArgument;

/**
 * Class GetDataFromNSIACommand
 */
//class GetDataFromNSIACommand extends ContainerAwareCommand
class GetDataFromNSIACommand extends Command
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
            ->setName('app:nsia:get')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('COMMAND app:nsia:get - START');

		$this->communicationNSIA->getDataFilesFromNSIA($output);

		$output->writeln('COMMAND app:nsia:get - END');

//TODO: gestione errore?
        return Command::SUCCESS;
    }
}
