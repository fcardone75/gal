<?php


namespace App\Controller;


use App\Service\Nsia\CommunicationNSIA;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
//use Symfony\Component\Finder\Finder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NsiaController extends AbstractController
{

    /**
     * @var FilesystemMap
     */
    private $filesystemMap;

    /**
     * @var CommunicationNSIA
     */
    private $communicationNSIA;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;


    public function __construct(FilesystemMap $filesystemMap, CommunicationNSIA $communicationNSIA, ParameterBagInterface $parameterBag)
    {
        $this->filesystemMap = $filesystemMap;
        $this->communicationNSIA = $communicationNSIA;
        $this->parameterBag = $parameterBag;
    }

    /**
     * @return Response
     * @Route(path="/getDataFromNsia", name="get_data_from_nsia")
     */
    public function getDataFromNsia(): Response
    {
//        dd('getDataFromNsia');
        $this->communicationNSIA->getDataFilesFromNSIA();
        return new Response();
    }

    /**
     * @return Response
     * @Route(path="/parseDataNsia", name="parse_data_nsia")
     */
    public function parseDataNsia(): Response
    {
        $this->communicationNSIA->parseDataFilesNSIA();
        return new Response();
    }

    /**
     * @return Response
     * @Route(path="/sendDataToNsia", name="send_data_to_nsia")
     */
    public function sendDataToNsia(): Response
    {
        $this->communicationNSIA->sendDataXMLToNSIA();
        return new Response();
    }

    /**
     * @return Response
     * @Route(path="/sendSyncLogToS3", name="send_sync_log_to_S3")
     */
    public function sendSyncLogToS3(): Response
    {

        if (!$this->filesystemMap->has('application_nsia_log')) {
            $message = 'ERROR: File system not found (application_nsia_log)';
            dd($message);
//            $this->logger->info($message);
//            $this->notifyError($message);
        }
        $fileSystem = $this->filesystemMap->get('application_nsia_log');

        $log_base_path = $this->getParameter('kernel.logs_dir');
        $prefix = 'application_communication_nsia*';

        $finder = new Finder();
        $finder->in($log_base_path)
            ->files()
            ->name($prefix);

        foreach($finder as $file){
            $fileSystem->write($file->getRelativePathname(),$file->getContents(),true);
        }

        return new Response();
    }
}
