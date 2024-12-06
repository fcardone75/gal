<?php

namespace App\Command;

use App\Entity\AtecoCode;
use App\Repository\AtecoCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Knp\Bundle\GaufretteBundle\FilesystemMap;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Class UpdateAtecoCodeCommand
 */
class UpdateAtecoCodeCommand extends Command
{

    private AtecoCodeRepository $atecoCodeRepository;
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;

    public function __construct(AtecoCodeRepository $atecoCodeRepository, EntityManagerInterface $entityManager, ParameterBagInterface $params, ?string $name = null)
    {
        $this->atecoCodeRepository = $atecoCodeRepository;
        $this->entityManager = $entityManager;
        $this->params = $params;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('app:ateco-code:import')
            ->addArgument('file_url', InputArgument::REQUIRED, 'insert file url to import')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('COMMAND app:ateco-code:import - START');

//        $basePath = $this->params->get('kernel.project_dir');
//        $file_path = $basePath.'\var\tmp\ateco_2022.csv';

        $file_path = $input->getArgument('file_url');

        $section = null;

        if (($handle = fopen($file_path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                if (!empty($data[0])) {
                    // gestione sezione: prima riga con un solo char
                    if (1 === strlen($data[0])  && preg_match("/^[A-Z]+$/", $data[0])) {
                        $section = $data[0];
                        continue;
                    }

                    if ($section && preg_match("/^[0-9.]+$/", $data[0])) {
                        $code = $data[0];
                        $codeWithoutDots = preg_replace('/[^0-9]/', '', $code);
                        $description = $data[1];

                        $criteria = ['codeWithoutDots' => $codeWithoutDots];
                        $ateco_code = $this->atecoCodeRepository->findOneBy($criteria);

                        if (empty($ateco_code)) {
                            $ateco_code = new AtecoCode();
                        }

                        $ateco_code->setCode($code);
                        $ateco_code->setCodeWithoutDots($codeWithoutDots);
                        $ateco_code->setDescription($description);
                        $ateco_code->setSection($section);

                        if (!$ateco_code->getId()) {
                            $this->entityManager->persist($ateco_code);
                        }
                        $this->entityManager->flush();
//dd($ateco_code);
                    }
                }
            }
            fclose($handle);
        }

		$output->writeln('COMMAND app:ateco-code:import - END');

//TODO: gestione errore?
        return Command::SUCCESS;
    }
}
