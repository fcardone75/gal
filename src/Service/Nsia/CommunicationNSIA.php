<?php


namespace App\Service\Nsia;

use App\Entity\AdditionalContribution;
use App\Entity\Application;
use App\Entity\ApplicationGroup;
use App\Entity\Confidi;
use App\Entity\RegistryFileAudit;
use App\Service\Contracts\MailerInterface;
use App\Service\ServerSFTP;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Gaufrette\FilesystemInterface;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use function Psl\Filesystem\get_filename;


class CommunicationNSIA
{
    use NsiaTrait;

//    const TEST_PATH_PREFIX = 'LIGURIA_';
//    const DEV_PATH_PREFIX = 'LIGURIA/';
//    const PROD_PATH_PREFIX = 'LIGURIA/';
    const FTP_PATH_PREFIX = 'LIGURIA';
    const DIR_XML_IN = 'NSIA_TO_WEB';
    const DIR_XML_OUT = 'WEB_TO_NSIA';

    private $callbackFunctionsManageFileToServer = [];

    private $newProgressiveNumber = 0;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ServerSFTP
     */
    private $serverSFTP;

    /**
     * @var ApplicationRegistryDataXmlManager
     */
    private $applicationRegistryDataXmlManager;

    /**
     * @var FilesystemMap
     */
    private $filesystemMap;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var bool
     */
    private $ftpEnabled;

    /**
     * @var string
     */
    private $ftpEnvironment;


    /**
     * CommunicationNSIA constructor.
     * @param EntityManagerInterface $entityManager
     * @param ServerSFTP $serverSFTP
     * @param ApplicationRegistryDataXmlManager $applicationRegistryDataXmlManager
     * @param FilesystemMap $filesystemMap
     * @param LoggerInterface $logger
     * @param MailerInterface $mailer
     * @param string $basePath
     * @param bool $ftpEnabled
     * @param string $ftpEnvironment
     */
    public function __construct(EntityManagerInterface            $entityManager,
                                ServerSFTP                        $serverSFTP,
                                ApplicationRegistryDataXmlManager $applicationRegistryDataXmlManager,
                                FilesystemMap                     $filesystemMap,
                                LoggerInterface                   $logger,
                                MailerInterface                   $mailer,
                                string                            $basePath,
                                bool                              $ftpEnabled,
                                string                            $ftpEnvironment
    )
    {
        $this->entityManager = $entityManager;
        $this->serverSFTP = $serverSFTP;
        $this->applicationRegistryDataXmlManager = $applicationRegistryDataXmlManager;
        $this->filesystemMap = $filesystemMap;
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->basePath = $basePath;
        $this->ftpEnabled = $ftpEnabled;
        $this->ftpEnvironment = $ftpEnvironment;

        error_reporting(1);
        set_time_limit(1200);
//		ini_set('memory_limit', '2048M');
    }

    /**
     * @throws NotAcceptableHttpException
     */
    public function sendDataXMLToNSIA(OutputInterface $output = null): void
    {
        $fileType = RegistryFileAudit::TYPE_LIGDO;
        $dir = self::DIR_XML_OUT;
        $dirPath = realpath(__DIR__ . $_ENV['NSIA_OUT_PATH']);


        $message = 'CommunicationNSIA->sendDataXMLToNSIA - START (ftpEnabled: ' . $this->ftpEnabled . ', ftpEnvironment: ' . $this->ftpEnvironment . ', $dirPath: ' . $dirPath . ')';
        $this->logger->info($message);
        if (null !== $output) {
            $output->writeln($message);
        }
//dd($message);


        $result = $this->manageFileXmlByType($fileType, $dirPath);

        $fileXmlTemp = $result['fileXml'] ?? null;
        $filenameTmp = $result['filename'] ?? null;

//TODO: compilo xml con dati application + callback
        $this->applicationRegistryDataXmlManager->setDataToXmlApplicationRegistry($fileXmlTemp, $this->newProgressiveNumber);

        $message = 'setDataToXmlApplicationRegistry';
        $this->logger->info($message);
        if (null !== $output) {
            $output->writeln($message);
        }

        $registryFileAudit = null;
        foreach ($this->callbackFunctionsManageFileToServer as $callbackFunction) {
            $registryFileAudit = call_user_func($callbackFunction);
//                dd($registryFileAudit);
        }

        $this->callbackFunctionsManageFileToServer = [];

//TODO: verificare logica associazione con registry file audit per application e additional contribution
// eseguo di nuovo le query o metodo ad hoc nel repository?

// solo confidi che hanno qualcosa da inviare
        $criteria = [];
        $confidiList = $this->entityManager->getRepository(Confidi::class)->findAllForNsia($criteria);
//dd(count($confidiList));

        foreach ($confidiList as $confidi) {

            // solo applicationGroup con application o additional contribution non ancora inviate
            $criteria = [];
            $criteria['confidi'] = $confidi;
            $applicationGroupList = $this->entityManager->getRepository(ApplicationGroup::class)->findAllForNsia($criteria);

            foreach ($applicationGroupList as $applicationGroup) {

                // associo file generato
                if ($applicationGroup->getStatus() == ApplicationGroup::STATUS_REGISTERED && empty($applicationGroup->getRegistryFileAudit())) {
                    $applicationGroup->setStatus(ApplicationGroup::STATUS_SENT_TO_NSIA);
                    $applicationGroup->setRegistryFileAudit($registryFileAudit);
                }

//TODO: verificare logiche selezione
// elenco di application del confidi non ancora inviate (application e/o relative richieste contributo aggiuntive)
//TODO: check criteria
                $criteria = [];
                $criteria['applicationGroup'] = $applicationGroup;
                $applicationList = $this->entityManager->getRepository(Application::class)->findAllForNsia($criteria);
//dd(count($applicationList));
                foreach ($applicationList as $application) {
// associo file generato
                    if (empty($application->getRegistryFileAudit())) {
                        $application->setRegistryFileAudit($registryFileAudit);
                    }

// associo file generato
                    if (!empty($application->getFinancingProvisioningCertification()) && empty($application->getFinancingProvisioningCertification()->getRegistryFileAudit())) {
                        $application->getFinancingProvisioningCertification()->setRegistryFileAudit($registryFileAudit);
                    }

                    $criteria = [];
                    $criteria['application'] = $application;
                    $additionalContributionList = $this->entityManager->getRepository(AdditionalContribution::class)->findAllForNsia($criteria);

                    foreach ($additionalContributionList as $additionalContribution) {
// associo file generato
                        $additionalContribution->setRegistryFileAudit($registryFileAudit);
                    }
                }
            }
        }
//die;
        $this->entityManager->flush();

        $message = 'CommunicationNSIA->sendDataXMLToNSIA - END';
        $this->logger->info($message);
        if (null !== $output) {
            $output->writeln($message);
        }
    }

    /**
     * @throws NotAcceptableHttpException
     */
    public function getDataFilesFromNSIA(OutputInterface $output = null): void
    {
        $fileType = RegistryFileAudit::TYPE_LIGREND;
        $dir = self::DIR_XML_IN;
        $dir = $this->verifyFtpPathByEnv($dir);
        $dirPath=realpath(__DIR__.$_ENV['NSIA_IN_PATH'].'/'.$dir);
        $dirPathOutput = realpath(__DIR__ .$_ENV['NSIA_OUT_PATH']);
        $message = 'CommunicationNSIA->getDataFilesFromNSIA - START (ftpEnabled: ' . $this->ftpEnabled . ', ftpEnvironment: ' . $this->ftpEnvironment . ', $dirPath: ' . $dirPath . ')';
        $this->logger->info($message);
        if (null !== $output) {
            $output->writeln($message);
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            if (strpos($file, $fileType . '_') === false) {
                continue;
            }


            $fileContent = file_get_contents($file);

            $this->logger->critical("OUTPUT: $dirPath" . get_filename($file));
            file_put_contents($dirPathOutput . '/' . get_filename($file), $fileContent, true);

            $message = 'fileSystem->write: ' . $dirPath . $file;
            $this->logger->info($message);
            if (null !== $output) {
                $output->writeln($message);
            }
        }

        $message = 'END getDataFilesFromNSIA';
        $this->logger->info($message);
        if (null !== $output) {
            $output->writeln($message);
        }
    }

    private function processDirectory(string $dirPath, string $fileType, FilesystemInterface $fileSystem, ?OutputInterface $output): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strpos($file->getFilename(), $fileType . '_') !== false) {
                $relativePath = substr($file->getPathname(), strlen($dirPath));
                $fileContent = file_get_contents($file->getPathname());
                $fileSystem->write($relativePath, $fileContent, true);

                $message = 'fileSystem->write: ' . $relativePath;
                $this->logger->info($message);
                if (null !== $output) {
                    $output->writeln($message);
                }
            }
        }
    }


    /**
     * @throws NotAcceptableHttpException
     */
    public function parseDataFilesNSIA(OutputInterface $output = null): void
    {
        $fileType = RegistryFileAudit::TYPE_LIGREND;
        $dirPath = realpath(__DIR__ . $_ENV['NSIA_OUT_PATH']);
        $dirPathTo = $dirPath . '/Elaborati/';

        $message = 'CommunicationNSIA->parseDataFilesNSIA - START (dirPath: ' . $dirPath . ', dirPathTo: ' . $dirPathTo . ')';
        $this->logger->info($message);
        if (null !== $output) {
            $output->writeln($message);
        }


        try {
            // Create Elaborati directory if it doesn't exist
            if (!file_exists($dirPathTo)) {
                mkdir($dirPathTo, 0777, true);
            }

            // TODO: elenco status x ciascuna entity
            $application_status_map = Application::$statusesNsiaMap;
            $additional_contribution_status_map = AdditionalContribution::$statusesNsiaMap;

            // Recursively iterate through all files in the directory
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                // Skip if it's not a file or if it's in the Elaborati directory
                if (!$file->isFile() || strpos($file->getPathname(), $dirPathTo) !== false) {
                    continue;
                }

                $file_name = $file->getBasename();
                // Skip if filename doesn't match the required pattern
                if (strpos($file_name, $fileType . '_') === false) {
                    continue;
                }

                $this->logger->critical("FILE PRESO: $file");

                $message = 'parse $file_name: ' . $file_name;
                $this->logger->info($message);

                // TODO: check nome file (numerazione coerente rispetto ad ultimo file elaborato)
                $criteria = [];
                $criteria['type'] = $fileType;

                $order = [];
                $order['progressiveNumber'] = 'DESC';

                $lastRegistryFileAudit = $this->entityManager->getRepository(RegistryFileAudit::class)->findOneBy($criteria, $order);
                $newProgressiveNumber = $lastRegistryFileAudit ? $lastRegistryFileAudit->getProgressiveNumber() + 1 : 1;
                $newProgressiveNumber = $this->formatFieldNumber($newProgressiveNumber, 4);
                $file_name_parts = explode('_', $file_name);

// Debug logging to understand the values
                $message = 'Debug - File name parts: ' . print_r($file_name_parts, true) .
                    ', Expected number: ' . $newProgressiveNumber;
                $this->logger->info($message);

// First, check if we have enough parts in the filename
                if (count($file_name_parts) < 2) {
                    $message = 'ERROR: Invalid file name format (' . $file_name . ')';
                    $this->logger->info($message);
                    $this->notifyError($message);
                    continue;
                }

                // TODO CAMBIATO DA 1 a 4
                $fileProgressiveNumber = $file_name_parts[2];

// Add debug logging for the comparison
                $message = 'Debug - Comparing file number: ' . $fileProgressiveNumber .
                    ' with expected number: ' . $newProgressiveNumber;
                $this->logger->info($message);

// If there's no last audit record, accept any properly formatted number
                if (!$lastRegistryFileAudit) {
                    // Verify that the number is properly formatted (4 digits)

                    $this->logger->critical("format: $fileProgressiveNumber");

                    if (!preg_match('/^\d{4}$/', $fileProgressiveNumber)) {
                        $message = 'ERROR: Invalid progressive number format in file (' . $file_name . ')';
                        $this->logger->info($message);
                        $this->notifyError($message);
                        continue;
                    }

                } else {
                    // TODO decommentare
                    // If we have a last audit record, verify the number is the expected one
                    if ($fileProgressiveNumber !== $newProgressiveNumber) {
                        $message = 'ERROR: il file inviato (' . $file_name . ') non ha il progressive number atteso (' . $newProgressiveNumber . ')';
                        $this->logger->info($message);
                        $this->notifyError($message);
                        continue;
                    }
                }

                // Read file content
                $fileContent = file_get_contents($file);
                if (empty($fileContent)) {
                    $message = 'ERROR: $file_name empty (' . $file_name . ')';
                    $this->logger->info($message);
                    $this->notifyError($message);
                    continue;
                }

                // SOME CODE WE DO NOT NEED START

                // Convert xml string into an object
                //TODO: commentato
//                $string_tmp = simplexml_load_string($fileContent);

// Convert into json
                //TODO: commentato
//                $json_tmp = json_encode($string_tmp);

// Convert into associative array
                $data_tmp = json_decode($fileContent, true);
//dd($data_tmp);
                $confidi_NSIA_counter = $data_tmp['@attributes']['NumeroConfidi'] ?? 0;
                $confidi_NSIA = $data_tmp['Confidi'] ?? [];
                if ($confidi_NSIA_counter && empty($confidi_NSIA)) {
                    $message = 'ERROR $confidi_NSIA empty';
                    $this->logger->info($message);
                    $this->notifyError($message);
                }

//TODO: verificare se esiste un solo nodo non viene letto come array di elementi
                $confidi_list = $this->checkArrayFormat($confidi_NSIA, 'CodiceNSIA');

                foreach ($confidi_list as $confidi) {
//<xs:element name="CodiceNSIA" type="String5" />
                    $CodiceNSIA = $confidi['CodiceNSIA'] ?? 'na';

                    $criteria = [];
                    $criteria['nsiaCode'] = $CodiceNSIA;
                    $confidiCtrl = $this->entityManager->getRepository(Confidi::class)->findOneBy($criteria);

//TODO: check confidi exist: se non esiste invio mail notifica
                    if (empty($confidiCtrl)) {
                        $message = 'ERROR $confidiCtrl empty (nsiaCode: ' . $CodiceNSIA . ')';
                        $this->logger->info($message);
                        $this->notifyError($message);
                    }

//<xs:element name="NumeroRiassicurazioni" type="xs:int" />  <!-- solo un campo di verifica per il numero di pratiche elencate sotto-->
                    $NumeroRiassicurazioni = $confidi['NumeroRiassicurazioni'] ?? null;

//<!-- I campi qui sotto vengono inviati da NSIA, resta da verificare se esporli sul portale e come...-->
//<xs:element name ="TotGarantito" type ="Importo"/>
                    $TotGarantito = $confidi['TotGarantito'] ?? null;
                    if (!empty($TotGarantito)) {
                        $confidiCtrl->setNsiaTotGarantito($TotGarantito);
                    }
//<xs:element name ="TotRiservaAccantonata" type ="Importo"/>
                    $TotRiservaAccantonata = $confidi['TotRiservaAccantonata'] ?? null;
                    if (!empty($TotRiservaAccantonata)) {
                        $confidiCtrl->setNsiaTotRiservaAccantonata($TotRiservaAccantonata);
                    }
//<xs:element name ="TotInefficace" type ="Importo"/>   <!-- Totale delle garanzie annullate, rinunciate o rese inefficaci per richiesta escussione rifiutata-->
                    $TotInefficace = $confidi['TotInefficace'] ?? null;
                    if (!empty($TotInefficace)) {
                        $confidiCtrl->setNsiaTotInefficace($TotInefficace);
                    }
//<xs:element name ="TotEscusso" type ="Importo"/>      <!-- L'anno è quello di approvazione, non quello di escussione -->
                    $TotEscusso = $confidi['TotEscusso'] ?? null;
                    if (!empty($TotEscusso)) {
                        $confidiCtrl->setNsiaTotEscusso($TotEscusso);
                    }
//<xs:element name ="TotEscutibile" type ="Importo"/>   <!-- il totale di quanto può ancora essere escusso delle pratiche in vita -->
                    $TotEscutibile = $confidi['TotEscutibile'] ?? null;
                    if (!empty($TotEscutibile)) {
                        $confidiCtrl->setNsiaTotEscutibile($TotEscutibile);
                    }
//<xs:element name ="NumeroPratichePresentate" type ="xs:int"/>
                    $NumeroPratichePresentate = $confidi['NumeroPratichePresentate'] ?? null;
                    if (!empty($NumeroPratichePresentate)) {
                        $confidiCtrl->setNsiaNumeroPratichePresentate($NumeroPratichePresentate);
                    }
//<xs:element name ="NumeroPraticheApprovate" type ="xs:int"/>
                    $NumeroPraticheApprovate = $confidi['NumeroPraticheApprovate'] ?? null;
                    if (!empty($NumeroPraticheApprovate)) {
                        $confidiCtrl->setNsiaNumeroPraticheApprovate($NumeroPraticheApprovate);
                    }
//<xs:element name ="NumeroPraticheInEssere" type ="xs:int"/>
                    $NumeroPraticheInEssere = $confidi['NumeroPraticheInEssere'] ?? null;
                    if (!empty($NumeroPraticheInEssere)) {
                        $confidiCtrl->setNsiaNumeroPraticheInEssere($NumeroPraticheInEssere);
                    }
//dd($confidiCtrl);
                    $riassicurazione_NSIA = $confidi['Riassicurazione'] ?? [];

//TODO: verificare se esiste un solo nodo non viene letto come array di elementi
                    $riassicurazione_list = $this->checkArrayFormat($riassicurazione_NSIA, 'CodicePraticaWEB');

//TODO: check coerenza dati riassicurazioni: se il totale non è coerente invio mail notifica

                    $NumeroRiassicurazioniCtrl = count($riassicurazione_list);

                    $this->logger->critical("Riassicurzioni: " . $NumeroRiassicurazioni);
                    $this->logger->critical("RiassicurzioniCtrl: " . $NumeroRiassicurazioniCtrl);

                    if ($NumeroRiassicurazioni != $NumeroRiassicurazioniCtrl) {
                        $message = 'ERROR $NumeroRiassicurazioni (' . $NumeroRiassicurazioni . ') non corrisponde numero $riassicurazione_NSIA (' . $NumeroRiassicurazioniCtrl . ')';
                        $this->logger->info($message);
                        $this->notifyError($message);
                    }

                    foreach ($riassicurazione_list as $riassicurazione) {
//<xs:element name="CodicePraticaWEB" type="String10" />
                        $CodicePraticaWEB = $riassicurazione['CodicePraticaWEB'] ?? 'na';

                        $criteria = [];
                        $criteria['id'] = $CodicePraticaWEB;
                        $this->logger->critical("Crit appl ctrl: " . json_encode($criteria));
                        $applicationCtrl = $this->entityManager->getRepository(Application::class)->findOneBy($criteria);

//TODO: check application exist: se non esiste invio mail notifica
                        if (empty($applicationCtrl)) {
                            $message = 'ERROR $applicationCtrl empty (id: ' . $CodicePraticaWEB . ')';
                            $this->logger->info($message);
                            $this->notifyError($message);
                        }

//<xs:element name="CodicePraticaConfidi" type="String20" />
                        $CodicePraticaConfidi = $riassicurazione['CodicePraticaConfidi'] ?? null;
                        if (!empty($CodicePraticaConfidi)) {
                            $applicationCtrl->setPracticeId($CodicePraticaConfidi);
                        }
//<xs:element name="NumeroPosizioneNSIA" type="String10" />
                        $NumeroPosizioneNSIA = $riassicurazione['NumeroPosizioneNSIA'] ?? null;
                        if (!empty($NumeroPosizioneNSIA)) {
                            $applicationCtrl->setNsiaNumeroPosizione($NumeroPosizioneNSIA);
                        }
//<xs:element name="FlagEnergia" type ="Character"/>   <!-- OBBLIGATORIO; valori ammessi S=Energia Sì, N=Energia No-->
                        $FlagEnergia = $riassicurazione['FlagEnergia'] ?? null;
                        if (!empty($FlagEnergia)) {
                            $applicationCtrl->setFlagEnergia($FlagEnergia);
                        }
//<xs:element name="DataProtocollo" type="xs:date" />
                        $DataProtocollo = $riassicurazione['DataProtocollo'] ?? null;
                        if ($DataProtocollo !== null) {
                            $DataProtocollo = new DateTime($DataProtocollo);
                            $applicationCtrl->setNsiaDataProtocollo($DataProtocollo);
                        }
//<xs:element name="CodiceStato" type="String5" />  <!-- Da vedere. la lista dei valori possibili..-->
//TODO: mappatura campo [OK]
                        $CodiceStato = $riassicurazione['CodiceStato'] ?? null;
                        if ($CodiceStato !== null) {
                            $status = $application_status_map[$CodiceStato]['status'];
                            if (!$status) {
                                $message = 'ERROR $application $CodiceStato non previsto (' . $CodiceStato . ')';
                                $this->logger->info($message);
                                $this->notifyError($message);
                            }
                            $applicationCtrl->setStatus($status);
                        }
//<xs:element name="Nota" type="String200" />
                        $Nota = $riassicurazione['Nota'] ?? null;
                        if ($Nota !== null) {
                            $applicationCtrl->setNsiaNota($Nota);
                        }
//<xs:element name="DataDelibera" type="xs:date" />         <!-- Negativa o positiva-->
                        $DataDelibera = $riassicurazione['DataDelibera'] ?? null;
                        if ($DataDelibera !== null) {
                            $DataDelibera = new DateTime($DataDelibera);
                            $applicationCtrl->setNsiaDataDelibera($DataDelibera);
                        }


//<!--per le pratiche deliberate, il codice COR è il codice rilasciato dal Registro Nazionale Aiuti per l'approvazione -->
//<xs:element name="CodiceCOR" type="xs:long" />
                        $CodiceCOR = $riassicurazione['CodiceCOR'] ?? null;
                        if ($CodiceCOR !== null) {
                            $applicationCtrl->setNsiaCodiceCor($CodiceCOR);
                        }
//<xs:element name="DataRilascioCOR" type="xs:date" />
                        $DataRilascioCOR = $riassicurazione['DataRilascioCOR'] ?? null;
                        if ($DataRilascioCOR !== null) {
                            $DataRilascioCOR = new DateTime($DataRilascioCOR);
                            $applicationCtrl->setNsiaDataRilascioCor($DataRilascioCOR);
                        }

//<xs:element name="TipoFinanziamento" type="Character" />
                        $TipoFinanziamento = $riassicurazione['TipoFinanziamento'] ?? null;

                        if ($TipoFinanziamento == 'F') {
                            $finanziamento_NSIA = $riassicurazione['Finanziamento'] ?? [];

//TODO: verificare se esiste un solo nodo non viene letto come array di elementi
                            $finanziamento_list = $this->checkArrayFormat($finanziamento_NSIA, 'CodiceNSIABanca');

//TODO: check coerenza dati finanziamento: se il totale non è coerente invio mail notifica
                            if (count($finanziamento_list) != 1) {
                                $message = 'ERROR $finanziamento_NSIA non presente';
                                $this->logger->info($message);
                                $this->notifyError($message);
                            }

                            foreach ($finanziamento_list as $finanziamento) {
//<xs:element name="CodiceNSIABanca" type="String5" />   <!-- decodifica da Foglio Excel -->
//TODO: mappatura campo [OK]
                                $CodiceNSIABanca = $finanziamento['CodiceNSIABanca'] ?? null;
                                $bank = $this->getApplicationBankByCode($applicationCtrl, $CodiceNSIABanca);
//TODO: check required?
                                if ($bank !== null) {
                                    $applicationCtrl->setFDbfBank($bank->getName());
                                }
//<xs:element name="Finalita" type="String5" />    <!-- decodifica da Foglio Excel -->
//TODO: mappatura campo [OK]
                                $Finalita = $finanziamento['Finalita'] ?? null;
                                $financialDestination = $this->getApplicationFinancialDestinationByCode($applicationCtrl, $Finalita);
//TODO: check required?
                                if ($financialDestination !== null) {
                                    $applicationCtrl->setFFinancialDestination($financialDestination->getDestination());
                                }
//<xs:element name="ImportoFinanziamento" type="Importo" />
                                $ImportoFinanziamento = $finanziamento['ImportoFinanziamento'] ?? null;
                                if ($ImportoFinanziamento !== null) {
                                    $applicationCtrl->setFDfAmount($ImportoFinanziamento);
                                }
//<xs:element name="DataFirmaContratto" type="xs:date" />
                                $DataFirmaContratto = $finanziamento['DataFirmaContratto'] ?? null;
                                if ($DataFirmaContratto !== null) {
                                    $DataFirmaContratto = new DateTime($DataFirmaContratto);
                                    $applicationCtrl->setFDfContractSignatureDate($DataFirmaContratto);
                                }
//<xs:element name="DataDeliberaBanca" type="xs:date" />
                                $DataDeliberaBanca = $finanziamento['DataDeliberaBanca'] ?? null;
                                if ($DataDeliberaBanca !== null) {
                                    $DataDeliberaBanca = new DateTime($DataDeliberaBanca);
                                    $applicationCtrl->setFDfResolutionDate($DataDeliberaBanca);
                                }
//<xs:element name="DataErogazione" type="xs:date" />
                                $DataErogazione = $finanziamento['DataErogazione'] ?? null;
                                if ($DataErogazione !== null) {
                                    $DataErogazione = new DateTime($DataErogazione);
                                    $applicationCtrl->setFDfIssueDate($DataErogazione);
                                }
//<xs:element name="DurataFinanziamento" type="xs:short" />
                                $DurataFinanziamento = $finanziamento['DurataFinanziamento'] ?? null;
                                if ($DurataFinanziamento !== null) {
                                    $applicationCtrl->setFDfDuration($DurataFinanziamento);
                                }
//<xs:element name="PeriodicitaRateAmmortamento" type="xs:short" />
//TODO: mappatura campo [OK]
                                $PeriodicitaRateAmmortamento = $finanziamento['PeriodicitaRateAmmortamento'] ?? null;
                                $applicationPeriodicity = $this->getApplicationPeriodicityByMonths($applicationCtrl, $PeriodicitaRateAmmortamento);
//TODO: check required?
                                if ($applicationPeriodicity !== null) {
//                                    $applicationCtrl->setApplicationPeriodicityF($applicationPeriodicity->getType());
                                    $applicationCtrl->getFDfPeriodicity($applicationPeriodicity->getType());
                                }
//<xs:element name="DataScadenzaPrimaRata" type="xs:date" />
                                $DataScadenzaPrimaRata = $finanziamento['DataScadenzaPrimaRata'] ?? null;
                                if ($DataScadenzaPrimaRata !== null) {
                                    $DataScadenzaPrimaRata = new DateTime($DataScadenzaPrimaRata);
                                    $applicationCtrl->setFDfFirstDepreciationDeadline($DataScadenzaPrimaRata);
                                }
//<xs:element name="ImportoRataAmmortamento" type="Importo" />
                                $ImportoRataAmmortamento = $finanziamento['ImportoRataAmmortamento'] ?? null;
                                if ($ImportoRataAmmortamento !== null) {
                                    $applicationCtrl->setFDfInstallmentAmount($ImportoRataAmmortamento);
                                }
//<xs:element name="TipologiaTasso" type="Character" /> <!-- valori ammessi F=Fisso, V=Variabile -->
                                $TipologiaTasso = $finanziamento['TipologiaTasso'] ?? null;
                                if ($TipologiaTasso !== null) {
                                    $applicationCtrl->setFTRateType($TipologiaTasso);
                                }
//<xs:element name="TassoFinanziamento" type="Percentuale" />
                                $TassoFinanziamento = $finanziamento['TassoFinanziamento'] ?? null;
                                if ($TassoFinanziamento !== null) {
                                    $applicationCtrl->setFTRate($TassoFinanziamento);
                                }
//<xs:element name="TAEG" type="Percentuale" />
                                $TAEG = $finanziamento['TAEG'] ?? null;
                                if ($TAEG !== null) {
                                    $applicationCtrl->setFTTaeg($TAEG);
                                }
//<xs:element name="EsistePreammortamento" type="Character" /> <!--  valori S' o 'N' -->
                                $EsistePreammortamento = $finanziamento['EsistePreammortamento'] ?? null;
                                if ($EsistePreammortamento !== null) {
                                    $applicationCtrl->setFDfPreDepreciationExists($EsistePreammortamento);
                                }
                            }
                        }

                        if ($TipoFinanziamento == 'L') {
                            $leasing_NSIA = $riassicurazione['Leasing'] ?? [];
//dd($leasing_NSIA);
//TODO: verificare se esiste un solo nodo non viene letto come array di elementi
                            $leasing_list = $this->checkArrayFormat($leasing_NSIA, 'CodiceNSIABancaLeasing');

//TODO: check coerenza dati leasing: se il totale non è coerente invio mail notifica
                            if (count($leasing_list) != 1) {
                                $message = 'ERROR $leasing_NSIA non presente';
                                $this->logger->info($message);
                                $this->notifyError($message);
                            }

                            foreach ($leasing_list as $leasing) {
//<xs:element name="CodiceNSIABancaLeasing" type="String5" /> <!-- decodifica da Foglio Excel -->
//TODO: mappatura campo [OK]
                                $CodiceNSIABancaLeasing = $leasing['CodiceNSIABancaLeasing'] ?? null;
                                $bankLeasing = $this->getApplicationBankLeasingByCode($applicationCtrl, $CodiceNSIABancaLeasing);
//TODO: check required?
                                if ($bankLeasing !== null) {
                                    $applicationCtrl->setLSfBankLeasing($bankLeasing->getName());
                                }
//<xs:element name="Finalita" type="String5" /> <!-- decodifica da Foglio Excel -->
//TODO: mappatura campo [OK]
                                $Finalita = $leasing['Finalita'] ?? null;
                                $leasingDestination = $this->getApplicationLeasingDestinationByCode($applicationCtrl, $Finalita);
//TODO: check required?
                                if ($leasingDestination !== null) {
                                    $applicationCtrl->setLSfLeasingDestination($leasingDestination->getDestination());
                                }
//<xs:element name="ImportoLeasing" type="Importo" />
                                $ImportoLeasing = $leasing['ImportoLeasing'] ?? null;
                                if ($ImportoLeasing !== null) {
                                    $applicationCtrl->setLDclAmount($ImportoLeasing);
                                }
//<xs:element name="DataFirmaContratto" type="xs:date" />
                                $DataFirmaContratto = $leasing['DataFirmaContratto'] ?? null;
                                if ($DataFirmaContratto !== null) {
                                    $DataFirmaContratto = new DateTime($DataFirmaContratto);
                                    $applicationCtrl->setLDclContractSignatureDate($DataFirmaContratto);
                                }
//<xs:element name="DataSottoscrizioneVerbale" type="xs:date" />   <!-- non lo riportiamo--> //TODO: ???
                                $DataSottoscrizioneVerbale = $leasing['DataSottoscrizioneVerbale'] ?? null;
                                if ($DataSottoscrizioneVerbale !== null) {
                                    $DataSottoscrizioneVerbale = new DateTime($DataSottoscrizioneVerbale);
                                    $applicationCtrl->setLDclResolutionDate($DataSottoscrizioneVerbale);
                                }
//<xs:element name="DurataLeasing" type="xs:short" />
                                $DurataLeasing = $leasing['DurataLeasing'] ?? null;
                                if ($DurataLeasing !== null) {
                                    $applicationCtrl->setLDclDuration($DurataLeasing);
                                }
//<xs:element name="PeriodicitaCanoni" type="xs:short" />
                                $PeriodicitaCanoni = $leasing['PeriodicitaCanoni'] ?? null;
                                if ($PeriodicitaCanoni !== null) {
                                    $applicationCtrl->setLDclPeriodicity($PeriodicitaCanoni);
                                }
//<xs:element name="DataScadenzaPrimoCanone" type="xs:date" />
                                $DataScadenzaPrimoCanone = $leasing['DataScadenzaPrimoCanone'] ?? null;
                                if ($DataScadenzaPrimoCanone !== null) {
                                    $DataScadenzaPrimoCanone = new DateTime($DataScadenzaPrimoCanone);
                                    $applicationCtrl->setLDclFirstDeadline($DataScadenzaPrimoCanone);
                                }
//<xs:element name="ImportoCanone" type="Importo" />
                                $ImportoCanone = $leasing['ImportoCanone'] ?? null;
                                if ($ImportoCanone !== null) {
                                    $applicationCtrl->setLDclFeeAmount($ImportoCanone);
                                }
//<xs:element name="PercentualeMacroCanone" type="Percentuale" />
                                $PercentualeMacroCanone = $leasing['PercentualeMacroCanone'] ?? null;
                                if ($PercentualeMacroCanone !== null) {
                                    $applicationCtrl->setLDclFeePercentage($PercentualeMacroCanone);
                                }
//<xs:element name="ImportoAnticipo" type="Importo" />
                                $ImportoAnticipo = $leasing['ImportoAnticipo'] ?? null;
                                if ($ImportoAnticipo !== null) {
                                    $applicationCtrl->setLDclFeeAmount($ImportoAnticipo);
                                }
//<xs:element name="PercentualeRiscatto" type="Percentuale" />
                                $PercentualeRiscatto = $leasing['PercentualeRiscatto'] ?? null;
                                if ($PercentualeRiscatto !== null) {
                                    $applicationCtrl->setLDclFeePercentage($PercentualeRiscatto);
                                }
//<xs:element name="ImportoRiscatto" type="Importo" />
                                $ImportoRiscatto = $leasing['ImportoRiscatto'] ?? null;
                                if ($ImportoRiscatto !== null) {
                                    $applicationCtrl->setNsiaLDclImportoRiscatto($ImportoRiscatto);
                                }
//<xs:element name="TassoLeasing" type="Percentuale" />
                                $TassoLeasing = $leasing['TassoLeasing'] ?? null;
                                if ($TassoLeasing !== null) {
                                    $applicationCtrl->setLDclRate($TassoLeasing);
                                }
                            }
                        }


//<xs:element name="ImportoGarantitoConfidi" type="Importo" /> // ImportoGarantitoConfidi -> ImportoGarantito
                        $ImportoGarantitoConfidi = $riassicurazione['ImportoGarantitoConfidi'] ?? null;
                        if ($ImportoGarantitoConfidi !== null) {
                            $applicationCtrl->setAeGAssuranceAmount($ImportoGarantitoConfidi);
                        }

//<xs:element name="DurataGaranzia" type="xs:short" />
                        $DurataGaranzia = $riassicurazione['DurataGaranzia'] ?? null;
                        if ($DurataGaranzia !== null) {
                            $applicationCtrl->setNsiaDurataGaranzia($DurataGaranzia);
                        }
//<xs:element name="ImportoRiassicurazione" type="Importo" />
                        $ImportoRiassicurazione = $riassicurazione['ImportoRiassicurazione'] ?? null;
                        if ($ImportoRiassicurazione !== null) {
                            $applicationCtrl->setNsiaImportoRiassicurazione($ImportoRiassicurazione);
                        }
//<xs:element name="ESLRiassicurazione" type="Importo" />
                        $ESLRiassicurazione = $riassicurazione['ESLRiassicurazione'] ?? null;
                        if ($ESLRiassicurazione !== null) {
                            $applicationCtrl->setNsiaEslRiassicurazione($ESLRiassicurazione);
                        }
//<xs:element name="DataInizioGaranzia" type="xs:date" />
                        $DataInizioGaranzia = $riassicurazione['DataInizioGaranzia'] ?? null;
                        if ($DataInizioGaranzia !== null) {
                            $DataInizioGaranzia = new DateTime($DataInizioGaranzia);
                            $applicationCtrl->setNsiaDataInizioGaranzia($DataInizioGaranzia);
                        }
//<xs:element name="DataFineGaranzia" type="xs:date" />
                        $DataFineGaranzia = $riassicurazione['DataFineGaranzia'] ?? null;
                        if ($DataFineGaranzia !== null) {
                            $DataFineGaranzia = new DateTime($DataFineGaranzia);
                            $applicationCtrl->setNsiaDataFineGaranzia($DataFineGaranzia);
                        }

                        $perdita_NSIA = $riassicurazione['Perdita'] ?? [];
//dd('$perdita_NSIA', $perdita_NSIA);
//TODO: verificare se esiste un solo nodo non viene letto come array di elementi
                        $perdita_list = $this->checkArrayFormat($perdita_NSIA, 'DataLiquidazioneConfidi');
//dd('$perdita_list', $perdita_list);

//TODO: check coerenza dati perdita: se il totale non è coerente invio mail notifica
//                        if (count($perdita_list) > 1) {
//                            $message = 'ERROR $perdita_NSIA errata';
//                            $this->logger->info($message);
//                            $this->notifyError($message);
//                        }

                        foreach ($perdita_list as $perdita) {
//<xs:element name="DataLiquidazioneConfidi" type="xs:date" />
                            $DataLiquidazioneConfidi = $perdita['DataLiquidazioneConfidi'] ?? null;
                            if ($DataLiquidazioneConfidi !== null) {
                                $DataLiquidazioneConfidi = new DateTime($DataLiquidazioneConfidi);
                                $applicationCtrl->setNsiaDataLiquidazioneConfidi($DataLiquidazioneConfidi);
                            }
//<xs:element name="ImportoPerditaConfidi" type="Importo" />
                            $ImportoPerditaConfidi = $perdita['ImportoPerditaConfidi'] ?? null;
                            if ($ImportoPerditaConfidi !== null) {
                                $applicationCtrl->setNsiaImportoPerditaConfidi($ImportoPerditaConfidi);
                            }
//<xs:element name="DataRichiestaRimborso" type="xs:date" />
                            $DataRichiestaRimborso = $perdita['DataRichiestaRimborso'] ?? null;
                            if ($DataRichiestaRimborso !== null) {
                                $DataRichiestaRimborso = new DateTime($DataRichiestaRimborso);
                                $applicationCtrl->setNsiaDataRichiestaRimborso($DataRichiestaRimborso);
                            }
//<xs:element name="DataProtocolloPerdita" type="xs:date" />
                            $DataProtocolloPerdita = $perdita['DataProtocolloPerdita'] ?? null;
                            if ($DataProtocolloPerdita !== null) {
                                $DataProtocolloPerdita = new DateTime($DataProtocolloPerdita);
                                $applicationCtrl->setNsiaDataProtocolloPerdita($DataProtocolloPerdita);
                            }
//<xs:element name="DataDeliberaPerdita" type="xs:date" />
                            $DataDeliberaPerdita = $perdita['DataDeliberaPerdita'] ?? null;
                            if ($DataDeliberaPerdita !== null) {
                                $DataDeliberaPerdita = new DateTime($DataDeliberaPerdita);
                                $applicationCtrl->setNsiaDataDeliberaPerdita($DataDeliberaPerdita);
                            }
//<xs:element name="ImportoRimborsoPrenotato" type="Importo" />
                            $ImportoRimborsoPrenotato = $perdita['ImportoRimborsoPrenotato'] ?? null;
                            if ($ImportoRimborsoPrenotato !== null) {
                                $applicationCtrl->setNsiaImportoRimborsoPrenotato($ImportoRimborsoPrenotato);
                            }
//<xs:element name="ImportoRimborsato" type="Importo" />
                            $ImportoRimborsato = $perdita['ImportoRimborsato'] ?? null;
                            if ($ImportoRimborsato !== null) {
                                $applicationCtrl->setNsiaImportoRimborsato($ImportoRimborsato);
                            }
//<xs:element name="DataLiquidazione" type="xs:date" />
                            $DataLiquidazione = $perdita['DataLiquidazione'] ?? null;
                            if ($DataLiquidazione !== null) {
                                $DataLiquidazione = new DateTime($DataLiquidazione);
                                $applicationCtrl->setNsiaDataLiquidazione($DataLiquidazione);
                            }
//<xs:element name="ImportoRestituitoConfidi" type="Importo" />
                            $ImportoRestituitoConfidi = $perdita['ImportoRestituitoConfidi'] ?? null;
                            if ($ImportoRestituitoConfidi !== null) {
                                $applicationCtrl->setNsiaImportoRestituitoConfidi($ImportoRestituitoConfidi);
                            }
//<xs:element name="DataRestituzioneConfidi" type="xs:date" />
                            $DataRestituzioneConfidi = $perdita['DataRestituzioneConfidi'] ?? null;
                            if ($DataRestituzioneConfidi !== null) {
                                $DataRestituzioneConfidi = new DateTime($DataRestituzioneConfidi);
                                $applicationCtrl->setNsiaDataRestituzioneConfidi($DataRestituzioneConfidi);
                            }
                        }

//<xs:element name="NumeroContributiAggiuntivi" type="xs:int" />   <!-- solo un campo di verifica per il numero di contributi elencate sotto-->
                        $NumeroContributiAggiuntivi = $riassicurazione['NumeroContributiAggiuntivi'] ?? null;

                        $contributo_aggiuntivo_NSIA = $riassicurazione['ContributoAggiuntivo'] ?? [];

//TODO: verificare se esiste un solo nodo non viene letto come array di elementi
                        $contributo_aggiuntivo_list = $this->checkArrayFormat($contributo_aggiuntivo_NSIA, 'TipoContributo');

                        $NumeroContributiAggiuntiviCtrl = count($contributo_aggiuntivo_list);

//TODO: check coerenza dati contributi aggiuntivi: se il totale non è coerente invio mail notifica
                        if ($NumeroContributiAggiuntivi != $NumeroContributiAggiuntiviCtrl) {
                            $message = 'ERROR $NumeroContributiAggiuntivi (' . $NumeroContributiAggiuntivi . ') non corrisponde numero $contributo_aggiuntivo_NSIA (' . $NumeroContributiAggiuntiviCtrl . ')';
                            $this->logger->info($message);
                            $this->notifyError($message);
                        }

                        foreach ($contributo_aggiuntivo_list as $contributo_aggiuntivo) {

//<!-- Il campo seguente va valorizzato con "ABB" per Abbuono commissioni, "CON" per
//contributo in conto/Interressi o Canone,  "CFP" per Contributo a Fondo Perduto  -->
//<xs:element name="TipoContributo" type="xs:string" />
                            $TipoContributo = $contributo_aggiuntivo['TipoContributo'] ?? 'na';

                            $criteria = [];
                            $criteria['application'] = $applicationCtrl->getId();
                            $criteria['type'] = $TipoContributo;
                            $this->logger->critical("add cont crit " . json_encode($criteria));
                            $additionalContributionCtrl = $this->entityManager->getRepository(AdditionalContribution::class)->findOneBy($criteria);
//print_r($criteria);
// mantis 0009906: LIGUARIA GAL & CCL - Richiesta sviluppo componente conto capitale
//TODO: check additionalContribution exist: se non esiste invio mail notifica
//                            if (empty($additionalContributionCtrl)) {
////dd($criteria, $additionalContributionCtrl);
//                                $message = 'ERROR $additionalContributionCtrl empty (application.id: '.$applicationCtrl->getId().', type: '.$TipoContributo.')';
//                                $this->logger->info($message);
//                                $this->notifyError($message);
//                            }

//TODO: add contributo aggiuntivo se non associato a $applicationCtrl [solo tipo TYPE_CFP]
                            if (empty($additionalContributionCtrl)) {
                                if ($TipoContributo == AdditionalContribution::TYPE_CFP) {
                                    $additionalContributionCtrl = new AdditionalContribution();
                                    $additionalContributionCtrl
                                        ->setType($TipoContributo)
                                        ->setInImport(false)
                                        ->setPresentationDate(new \DateTime());
                                } else {
                                    $message = 'ERROR $additionalContributionCtrl empty (application.id: ' . $applicationCtrl->getId() . ', type: ' . $TipoContributo . ')';
                                    $this->logger->info($message);
                                    $this->notifyError($message);
                                }
                            }
// mantis 0009906: LIGUARIA GAL & CCL - Richiesta sviluppo componente conto capitale

//<xs:element name="NumeroPosizioneNSIA" type="xs:string" />
                            $NumeroPosizioneNSIA = $contributo_aggiuntivo['NumeroPosizioneNSIA'] ?? null;
                            if ($NumeroPosizioneNSIA !== null) {
                                $additionalContributionCtrl->setNsiaNumeroPosizione($NumeroPosizioneNSIA);
                            }
//<xs:element name="DataPresentazioneDomanda" type="xs:date" />
//TODO: format function [OK]
                            $DataPresentazioneDomanda = $contributo_aggiuntivo['DataPresentazioneDomanda'] ?? null;
                            if ($DataPresentazioneDomanda !== null) {
                                $DataPresentazioneDomanda = new DateTime($DataPresentazioneDomanda);
                                $additionalContributionCtrl->setPresentationDate($DataPresentazioneDomanda);
                            }
//<xs:element name="CodiceStato" type="String5" /> <!-- Da vedere. la lista dei valori possibili..-->
//TODO: add field + mappatura campo [OK]
                            $CodiceStato = $contributo_aggiuntivo['CodiceStato'] ?? null;
                            if ($CodiceStato !== null) {
                                $status = $additional_contribution_status_map[$CodiceStato]['status'];
                                if (!$status) {
                                    $message = 'ERROR $additionalContribution $CodiceStato non previsto (' . $CodiceStato . ')';
                                    $this->logger->info($message);
                                    $this->notifyError($message);
                                }
                                $additionalContributionCtrl->setNsiaStatus($status);
                            }
//<xs:element name="Nota" type="String200" />
                            $Nota = $contributo_aggiuntivo['Nota'] ?? null;
                            if ($Nota !== null) {
                                $additionalContributionCtrl->setNsiaNota($Nota);
                            }

//<xs:element name="DataDelibera" type="xs:date" />  <!-- Negativa o positiva-->
                            $DataDelibera = $contributo_aggiuntivo['DataDelibera'] ?? null;
                            if ($DataDelibera !== null) {
                                $DataDelibera = new DateTime($DataDelibera);
                                $additionalContributionCtrl->setNsiaDataDelibera($DataDelibera);
                            }
//<!--per le pratiche deliberate, il codice COR è il codice rilasciato dal Registro Nazionale Aiuti per l'approvazione -->
//<xs:element name="CodiceCOR" type="xs:long" />
                            $CodiceCOR = $contributo_aggiuntivo['CodiceCOR'] ?? null;
                            if ($CodiceCOR !== null) {
                                $additionalContributionCtrl->setNsiaCodiceCor($CodiceCOR);
                            }
//<xs:element name="DataRilascioCOR" type="xs:date" />
                            $DataRilascioCOR = $contributo_aggiuntivo['DataRilascioCOR'] ?? null;
                            if ($DataRilascioCOR !== null) {
                                $DataRilascioCOR = new DateTime($DataRilascioCOR);
                                $additionalContributionCtrl->setNsiaDataRilascioCor($DataRilascioCOR);
                            }

//<xs:element name="ImportoContributoDeliberato" type="Importo" />
                            $ImportoContributoDeliberato = $contributo_aggiuntivo['ImportoContributoDeliberato'] ?? null;
                            if ($ImportoContributoDeliberato !== null) {
                                $additionalContributionCtrl->setNsiaImportoContributoDeliberato($ImportoContributoDeliberato);
                            }
//<xs:element name="ImportoContributoLiquidato" type="Importo" />
                            $ImportoContributoLiquidato = $contributo_aggiuntivo['ImportoContributoLiquidato'] ?? null;
                            if ($ImportoContributoLiquidato !== null) {
                                $additionalContributionCtrl->setNsiaImportoContributoLiquidato($ImportoContributoLiquidato);
                            }
//<xs:element name="DataLiquidazione" type="xs:date" />
                            $DataLiquidazione = $contributo_aggiuntivo['DataLiquidazione'] ?? null;
                            if ($DataLiquidazione !== null) {
                                $DataLiquidazione = new DateTime($DataLiquidazione);
                                $additionalContributionCtrl->setNsiaDataLiquidazione($DataLiquidazione);
                            }
//<xs:element name="IBANLiquidazione" type ="xs:string" />
                            $IBANLiquidazione = $contributo_aggiuntivo['IBANLiquidazione'] ?? null;
                            if ($IBANLiquidazione !== null) {
                                $additionalContributionCtrl->setNsiaIbanLiquidazione($IBANLiquidazione);
                            }

                            $revoca_NSIA = $contributo_aggiuntivo['Revoca'] ?? [];

//TODO: verificare se esiste un solo nodo non viene letto come array di elementi
                            $revoca_list = $this->checkArrayFormat($revoca_NSIA, 'DataRevoca');

//TODO: check coerenza dati revoca: se il totale non è coerente invio mail notifica
                            if (count($revoca_list) > 1) {
                                $message = 'ERROR $revoca_NSIA errata';
                                $this->logger->info($message);
                                $this->notifyError($message);
                            }

                            foreach ($revoca_list as $revoca) {
//<xs:element name="DataRevoca" type="xs:date" /> <!-- data dell'evento che ha generato la revoca-->
                                $DataRevoca = $revoca['DataRevoca'] ?? null;
                                if ($DataRevoca !== null) {
                                    $DataRevoca = new DateTime($DataRevoca);
                                    $additionalContributionCtrl->setNsiaDataRevoca($DataRevoca);
                                }
//<xs:element name="MotivoRevoca" type="String200" /> <!-- descrizione estesa variazione -->
                                $MotivoRevoca = $revoca['MotivoRevoca'] ?? null;
                                if ($MotivoRevoca !== null) {
                                    $additionalContributionCtrl->setNsiaMotivoRevoca($MotivoRevoca);
                                }
//<xs:element name="ImportoContributoRevocato" type="Importo" />
                                $ImportoContributoRevocato = $contributo_aggiuntivo['ImportoContributoRevocato'] ?? null;
                                if ($ImportoContributoRevocato !== null) {
                                    $additionalContributionCtrl->setNsiaImportoContributoRevocato($ImportoContributoRevocato);
                                }
//<xs:element name="DataAvvioProcedimentoRevoca" type="xs:date" />
                                $DataAvvioProcedimentoRevoca = $revoca['DataAvvioProcedimentoRevoca'] ?? null;
                                if ($DataAvvioProcedimentoRevoca !== null) {
                                    $DataAvvioProcedimentoRevoca = new DateTime($DataAvvioProcedimentoRevoca);
                                    $additionalContributionCtrl->setNsiaDataAvvioProcedimentoRevoca($DataAvvioProcedimentoRevoca);
                                }
//<xs:element name="ImportoRecuperoDovuto" type="Importo" />
                                $ImportoRecuperoDovuto = $revoca['ImportoRecuperoDovuto'] ?? null;
                                if ($ImportoRecuperoDovuto !== null) {
                                    $additionalContributionCtrl->setNsiaImportoRecuperoDovuto($ImportoRecuperoDovuto);
                                }
//<xs:element name="InteressiDovuti" type="Importo" />
                                $InteressiDovuti = $revoca['InteressiDovuti'] ?? null;
                                if ($InteressiDovuti !== null) {
                                    $additionalContributionCtrl->setNsiaInteressiDovuti($InteressiDovuti);
                                }
//<xs:element name="DataRichiestaRecupero" type="xs:date" />
                                $DataRichiestaRecupero = $revoca['DataRichiestaRecupero'] ?? null;
                                if ($DataRichiestaRecupero !== null) {
                                    $DataRichiestaRecupero = new DateTime($DataRichiestaRecupero);
                                    $additionalContributionCtrl->setNsiaDataRichiestaRecupero($DataRichiestaRecupero);
                                }
//<xs:element name="ImportoContributoRestituito" type="Importo" /> // TODO: verificare formato ??? -> Importo
                                $ImportoContributoRestituito = $revoca['ImportoContributoRestituito'] ?? null;
                                if ($ImportoContributoRestituito !== null) {
                                    $additionalContributionCtrl->setNsiaImportoContributoRestituito($ImportoContributoRestituito);
                                }
//<xs:element name="ImportoInteressiRestituiti" type="Importo" />
                                $ImportoInteressiRestituiti = $revoca['ImportoInteressiRestituiti'] ?? null;
                                if ($ImportoInteressiRestituiti !== null) {
                                    $additionalContributionCtrl->setNsiaImportoInteressiRestituiti($ImportoInteressiRestituiti);
                                }
//<xs:element name="DataRestituzione" type="Importo" /> <!-- data valuta recupero dell'ultima rata incassata -->
                                $DataRestituzione = $revoca['DataRestituzione'] ?? null;
                                if ($DataRestituzione !== null) {
                                    $DataRestituzione = new DateTime($DataRestituzione);
                                    $additionalContributionCtrl->setNsiaDataRestituzione($DataRestituzione);
                                }
                            }

// mantis 0009906: LIGUARIA GAL & CCL - Richiesta sviluppo componente conto capitale
                            if (!$additionalContributionCtrl->getId()) {
                                $this->entityManager->persist($additionalContributionCtrl);
                                $applicationCtrl->addAdditionalContribution($additionalContributionCtrl);
                            }
// mantis 0009906: LIGUARIA GAL & CCL - Richiesta sviluppo componente conto capitale

                        }

                        $impresa_NSIA = $riassicurazione['Impresa'] ?? [];

//TODO: verificare se esiste un solo nodo non viene letto come array di elementi
                        $impresa_list = $this->checkArrayFormat($impresa_NSIA, 'CodiceFiscale');

//TODO: check coerenza dati impresa: se impresa non è coerente invio mail notifica
                        if (count($impresa_list) != 1) {
                            $message = 'ERROR $impresa_NSIA errata';
                            $this->logger->info($message);
                            $this->notifyError($message);
                        }

                        foreach ($impresa_list as $impresa) {
//<xs:element name="CodiceFiscale" type="String16" />
                            $CodiceFiscale = $impresa['CodiceFiscale'] ?? null;
                            if ($CodiceFiscale !== null) {
                                $applicationCtrl->setAeIbFiscalCode($CodiceFiscale);
                            }
//<xs:element name="RagioneSociale" type="String60" />
                            $RagioneSociale = $impresa['RagioneSociale'] ?? null;
                            if ($RagioneSociale !== null) {
                                $applicationCtrl->setAeIbBusinessName($RagioneSociale);
                            }
//<xs:element name="FormaGiuridica" type="String5" />
//TODO: mappatura campo [OK]
                            $FormaGiuridica = $impresa['FormaGiuridica'] ?? null;
                            $legalForm = $this->getApplicationLegalFormByReferenceId($applicationCtrl, $FormaGiuridica);
//TODO: check required?
                            if ($legalForm !== null) {
                                $applicationCtrl->setAeIbLegalForm($legalForm->getName());
                            }
//<xs:element name="CodiceCCIA" type="String10" />
                            $CodiceCCIA = $impresa['CodiceCCIA'] ?? null;
                            if ($CodiceCCIA !== null) {
                                $applicationCtrl->setAeIbChamberOfCommerceCode($CodiceCCIA);
                            }
//<xs:element name="DataIscrizioneCCIA" type="xs:date" />
                            $DataIscrizioneCCIA = $impresa['DataIscrizioneCCIA'] ?? null;
                            if ($DataIscrizioneCCIA !== null) {
                                $DataIscrizioneCCIA = new DateTime($DataIscrizioneCCIA);
                                $applicationCtrl->setAeIbChamberOfCommerceRegistrationDate($DataIscrizioneCCIA);
                            }
//<xs:element name="CodiceAIA" type="String10" />
                            $CodiceAIA = $impresa['CodiceAIA'] ?? null;
                            if ($CodiceAIA !== null) {
                                $applicationCtrl->setAeIbAIACode($CodiceAIA);
                            }
//<xs:element name="DataIscrizioneAIA" type="xs:date" />
                            $DataIscrizioneAIA = $impresa['DataIscrizioneAIA'] ?? null;
                            if ($DataIscrizioneAIA !== null) {
                                $DataIscrizioneAIA = new DateTime($DataIscrizioneAIA);
                                $applicationCtrl->getAeIbAIARegistrationDate($DataIscrizioneAIA);
                            }
//<xs:element name="CodiceAttivitaATECO" type="String10" />
                            $CodiceAttivitaATECO = $impresa['CodiceAttivitaATECO'] ?? null;
                            if ($CodiceAttivitaATECO !== null) {
                                $applicationCtrl->setAeIbAtecoCode($CodiceAttivitaATECO);
                            }
                        }
                    }
                }

// salvo info del file elaborato
                $registryFileAudit = new RegistryFileAudit();
                $registryFileAudit
                    ->setFileName($file_name)
                    ->setProgressiveNumber($newProgressiveNumber)
                    ->setType($fileType);
                $this->entityManager->persist($registryFileAudit);
//dd($registryFileAudit);
//                die();
                $this->entityManager->flush();
//dd($dirPath, $dirPathTo, $file_name_with_path);
//                $file_name_with_path_new = str_replace($dirPath, $dirPathTo, $file_name_with_path);
//dd($dirPath, $dirPathTo, $file_name_with_path, $file_name_with_path_new);
                // SOME CODE WE DO NOT NEED END

                // Move file to Elaborati directory
                $file_name_with_path_new = $dirPathTo . $file_name;

                $this->logger->critical("NOME FILE: $file");
                $this->logger->critical("RINOMINA IN $file_name_with_path_new");
                file_put_contents($file_name_with_path_new, file_get_contents($file));
//                if (!rename($file, $file_name_with_path_new)) {
//                    $message = 'ERROR: Failed to move file to Elaborati directory';
//                    $this->logger->error($message);
//                    $this->notifyError($message);
//                    continue;
//                }
                $message = 'File moved: ' . $file . ' -> ' . $file_name_with_path_new;
                $this->logger->info($message);
            }

            $message = 'END parseDataFilesNSIA';
            $this->logger->info($message);
            if (null !== $output) {
                $output->writeln($message);
            }

        } catch (\Exception $e) {
            $message = 'ERROR: Exception occurred while processing files: ' . $e->getMessage();
            $this->logger->error($message);
            $this->notifyError($message);
        }
    }


    /**
     * @throws NotAcceptableHttpException
     */
    private function manageFileXmlByType(string $fileType, string $dirPath): array
    {
        $message = 'START manageFileXmlByType';
        $this->logger->info($message);

//        $lastRegistryFileAudit = $this->entityManager->getRepository(RegistryFileAudit::class)->findOneBy(
//            ['type' => $fileType],
//            ['progressiveNumber' => 'DESC']
//        );
        $criteria = [];
        $criteria['type'] = $fileType;

        $order = [];
        $order['progressiveNumber'] = 'DESC';
        $lastRegistryFileAudit = $this->entityManager->getRepository(RegistryFileAudit::class)->findOneBy($criteria, $order);
//dd($lastRegistryFileAudit);
        $newProgressiveNumber = $lastRegistryFileAudit ? $lastRegistryFileAudit->getProgressiveNumber() + 1 : 1;
        $filename = sprintf(
            '%1$s_%2$s_%3$s.xml',
            $fileType,
            $this->formatFieldNumber($newProgressiveNumber, 4),
            date('Ymd')
        );
        $message = '$registryFileAuditType: ' . $fileType . ' - $newProgressiveNumber: ' . $newProgressiveNumber . ' - $filename: ' . $filename;
        $this->logger->info($message);

//        if (!$this->filesystemMap->has('application_nsia_xml')) {
//            $message = 'ERROR: File system not found (application_nsia_xml)';
//            $this->logger->info($message);
//            $this->notifyError($message);
//        }
//
//        $fileSystem = $this->filesystemMap->get('application_nsia_xml');

        $this->newProgressiveNumber = $newProgressiveNumber;

//TODO: create tmp file in tmp dir
//        $dir_local_tmp = $this->basePath . '/var/tmp/nsia_tmp/';
//        $filesystemTmp = new Filesystem();
//        if (!$filesystemTmp->exists($dir_local_tmp)) {
//            $filesystemTmp->mkdir($dir_local_tmp, 0777);
//        }
//
//TODO: verificare gestione delete file old
//        $finder = new Finder();
//        $finder->files()->in($dir_local_tmp)->date('until 10 minutes ago');
//        foreach ($finder as $file) {
//            $filesystemTmp->remove($file);
//        }

// genero file xml
        $fileXml = $filename;
        $message = '$fileXml: ' . $fileXml;
        $this->logger->info($message);

        $this->callbackFunctionsManageFileToServer[] = function () use ($fileType, $fileXml, $filename, $newProgressiveNumber, $dirPath) {
//            if (!$this->serverSFTP->file_exists($dirPath)) {
//                $this->serverSFTP->mkdir($dirPath);
//            }
//            fclose($fileXml);

            file_put_contents($dirPath . '/' . $filename, file_get_contents($filename));
//            $this->serverSFTP->put($dirPath . $filename, file_get_contents($dir_local_tmp . $filename));
            $message = 'serverSFTP->put: ' . $dirPath . $filename;
            $this->logger->info($message);

            $registryFileAudit = new RegistryFileAudit();
            $registryFileAudit
                ->setFileName($filename)
                ->setProgressiveNumber($newProgressiveNumber)
                ->setType($fileType);
            $this->entityManager->persist($registryFileAudit);

//TODO: copio file su dir bkp

            $fileContent = file_get_contents($filename);

            file_put_contents($dirPath . '/' . $filename, $fileContent, true);
//            $fileSystem->write($dirPath . $filename, $fileContent, true);
            $message = '$fileSystem->write: ' . $dirPath . $filename;
            $this->logger->info($message);
//die;
//            unlink($dir_local_tmp . $filename);

            $message = 'END manageFileXmlByType';
            $this->logger->info($message);
            return $registryFileAudit;
        }; // end callback

        return ['fileXml' => $fileXml, 'filename' => $filename];
    }

    private function checkArrayFormat(array $array_to_verify, string $field_ctrl)
    {
        $result = [];
        if (!empty($array_to_verify)) {
            $fied_tmp = array_key_first($array_to_verify);
            if ($fied_tmp == '0') {
                $result = $array_to_verify;
            } elseif ($fied_tmp == $field_ctrl) {
                $result[0] = $array_to_verify;
            } else {
                $message = 'ERROR $array_to_verify format error';
                $this->logger->info($message);
                $this->notifyError($message);
            }
        }
        return $result;
    }

    private function verifyFtpPathByEnv(string $dir): string
    {
        $prefix = '';
        if ($this->ftpEnvironment == 'dev') {
            $prefix = self::FTP_PATH_PREFIX . '_' . $this->ftpEnvironment . '_';
        } elseif ($this->ftpEnvironment == 'test') {
            $prefix = self::FTP_PATH_PREFIX . '/' . $this->ftpEnvironment . '_';
        } elseif ($this->ftpEnvironment == 'prod') {
            $prefix = self::FTP_PATH_PREFIX . '/';
        } else {
            $message = 'ERROR invalid ftpEnvironment (' . $this->ftpEnvironment . ')';
            $this->logger->info($message);
            $this->notifyError($message);
        }
//        dd($prefix . $dir . '/');
        return $prefix . $dir . '/';
    }

}
