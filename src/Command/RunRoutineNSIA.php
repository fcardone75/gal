<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Style\SymfonyStyle;

class RunRoutineNSIA extends Command
{
    protected static $defaultName = 'app:nsia:routine';

    protected function configure()
    {
        $this
            ->setDescription('File routine execution for NSIA');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        //// Esecuzione del primo comando
        //$inputArgs = new ArrayInput([
        //    'ftp-enabled' => true,
        //    'ftp-environment' => 'prod'
        //]);
        //$nsiaCheckVPNCommand = $this->getApplication()->find('app:check:vpn');
        //$nsiaCheckVPNCommand->run($inputArgs,$output);
//
        //$nsiaGetCommand = $this->getApplication()->find('app:nsia:get');
        //$nsiaGetCommand->run((new ArrayInput([])), $output);
//
        //$nsiaParseCommand = $this->getApplication()->find('app:nsia:parse');
        //$nsiaParseCommand->run((new ArrayInput([])), $output);
//
        //$nsiaSendCommand = $this->getApplication()->find('app:nsia:send');
        //$nsiaSendCommand->run((new ArrayInput([])), $output);
//
        //$nsiaLogCommand = $this->getApplication()->find('app:sendSyncLog:S3');
        //$nsiaLogCommand->run((new ArrayInput([])), $output);
//
        $inputArgs = new ArrayInput([
            'transport' => 'mail',
            'email' => 'artigiancassa-services@synesthesia.it'
        ]);
        $mailCommand = $this->getApplication()->find('app:send-test-email');
        $mailCommand->run($inputArgs,$output);

        $io->success('Tutti i comandi sono stati eseguiti con successo.');

        return Command::SUCCESS;
    }
}
