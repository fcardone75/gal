<?php

namespace App\Command;

use App\Service\ServerSFTP;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class CheckVpnBnlCommand
 */
class CheckVpnBnlCommand extends Command
{
    /**
     * @param ServerSFTP $serverSFTP
     */
    private $serverSFTP;

    public function __construct(ServerSFTP $serverSFTP)
    {
        $this->serverSFTP = $serverSFTP;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:check:vpn')
            ->addArgument('ftp-enabled', InputArgument::OPTIONAL, 'If FTP is enabled',true)
            ->addArgument('ftp-environment', InputArgument::OPTIONAL, 'FTP environment', 'test');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('COMMAND CHECK VPN - START');

        $ftpEnabled = $input->getArgument('ftp-enabled');
        $ftpEnvironment = $input->getArgument('ftp-environment');

        $output->writeln('params: $ftpEnabled = ' . $ftpEnabled . ' - $ftpEnvironment = ' . $ftpEnvironment);

        if($ftpEnabled && $ftpEnvironment == 'prod') {
            $this->serverSFTP->connectToServer();
            $result = $this->serverSFTP->nlist();
            dump($result);
        }

        $output->writeln('COMMAND CHECK VPN - END');
        return Command::SUCCESS;
    }
}
