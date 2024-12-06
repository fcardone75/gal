<?php

namespace App\Command;

use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class SendSyncLogToS3Command extends Command
{
    protected static $defaultName = 'app:sendSyncLog:S3';

    private $filesystemMap;
    private $logDir;

    public function __construct(FilesystemMap $filesystemMap, string $logDir)
    {
        $this->filesystemMap = $filesystemMap;
        $this->logDir = $logDir;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Send sync log to S3');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('COMMAND SEND SYNC LOG S3 - START');

        $log_base_path = $this->logDir;
        $prefix = 'xf_nsia-sync-*';

        $fileSystem = $this->filesystemMap->get('application_nsia_log');

        $finder = new Finder();
        $finder->in($log_base_path)
            ->files()
            ->name($prefix);

        foreach ($finder as $file) {
            $fileSystem->write($file->getRelativePathname(), $file->getContents(), true);
        }

        $output->writeln('COMMAND SEND SYNC LOG S3 - END');

        return Command::SUCCESS;
    }
}
