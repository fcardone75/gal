<?php

namespace App\Command;

use App\Service\Nsia\CommunicationNSIA;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class SendDataToNSIACommand
 */
class SendDataToNSIACommand extends Command
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
            ->setName('app:nsia:send')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$output->writeln('COMMAND app:nsia:send - START');

		$this->communicationNSIA->sendDataXMLToNSIA($output);

		$output->writeln('COMMAND app:nsia:send - END');
//TODO: gestione errore?
        return Command::SUCCESS;
	}
}
