<?php


namespace App\Service\Nsia;


use App\Entity\AdditionalContribution;
use App\Entity\Application;
use App\Entity\ApplicationGroup;
use App\Entity\Confidi;
use App\Entity\FinancingProvisioningCertification;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

use Spatie\ArrayToXml\ArrayToXml;

class ApplicationRegistryDataXmlManager
{
    use NsiaTrait;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var int
     */
    private $numeroConfidi;

    /**
     * @var int
     */
    private $numeroRiassicurazioni;

    /**
     * @var int
     */
    private $numeroContributiAggiuntivi;

//TODO: [???]
    /**
     * @var int
     */
    private $numeroAttestazioni;

    /**
     * CommunicationXF constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        $this->numeroConfidi = 0;
        $this->numeroRiassicurazioni = 0;
        $this->numeroContributiAggiuntivi = 0;
//TODO: [???]
        $this->numeroAttestazioni = 0;
    }


    public function setDataToXmlApplicationRegistry($fileXmlSource, $newProgressiveNumber = 1)
    {
// https://github.com/pravednik/xmlBundle

// init xml
		$xmlns_xsi = 'http//www.w3.org/2001/XMLSchema-instance';
		$xmlns_xsd = 'http//www.w3.org/2001/XMLSchema';
		$xmlns = 'http://farelazio.it/xsd';

		$tipoFlusso = 'LIGDO';
		$progressivo = $newProgressiveNumber;
		$dataCreazione = $this->checkFieldDate(new DateTime());

		$flusso = 'Flusso'.$tipoFlusso;

        $rootElamentName = $flusso;
        $rootAttributes = [
            'xmlns:xsi' => $xmlns_xsi,
            'xmlns:xsd' => $xmlns_xsd,
            'TipoFlusso' => $tipoFlusso,
            'Progressivo' => $progressivo,
            'DataCreazione' => $dataCreazione,
            'NumeroConfidi' => $this->numeroConfidi,
            'xmlns' => $xmlns
        ];

        $xml_data = [];

// solo confidi che hanno qualcosa da inviare
        $criteria = [];
        $confidiList = $this->entityManager->getRepository(Confidi::class)->findAllForNsia($criteria);
//dd(count($confidiList));

        foreach($confidiList as $confidi) {
            $resultConfidi = $this->createConfidiData($confidi);
            if ($resultConfidi) {
                $xml_data['Confidi'][] = $resultConfidi;

                $this->numeroConfidi ++;
                $this->numeroRiassicurazioni = 0;
                $this->numeroContributiAggiuntivi = 0;
                $this->numeroAttestazioni = 0;
            }
        }

        $rootAttributes['NumeroConfidi'] = $this->numeroConfidi;

        $data = ArrayToXml::convert(
            $xml_data,
            [
                'rootElementName' => $rootElamentName,
                '_attributes' => $rootAttributes,
            ],
            true,
            'UTF-8',
            '1.0',
            ['formatOutput' => true]
        );

        file_put_contents($fileXmlSource, $data);
//		fputs ($fileXmlSource, $data);
    }


    private function createConfidiData(Confidi $confidi = null)
    {
        $resultConfidi = [];

        //<xs:element name="CodiceNSIA" type="String5" />
        $CodiceNSIA = $this->checkFieldString($confidi->getNsiaCode(), 5);
        if (!empty($CodiceNSIA)) {
            $resultConfidi['CodiceNSIA'] = $CodiceNSIA;
        }

// solo applicationGroup con application o additional contribution non ancora inviate
        $criteria = [];
        $criteria['confidi'] = $confidi;
        $applicationGroupList = $this->entityManager->getRepository(ApplicationGroup::class)->findAllForNsia($criteria);
//dd(count($applicationGroupList));

//TODO: i dati di application group vanno inviati solo se in status protocollato

// first valid application group of confidi
        $validApplicationGroup = null;
        foreach($applicationGroupList as $applicationGroup) {
            if ($applicationGroup->getStatus() == ApplicationGroup::STATUS_REGISTERED) {
                $validApplicationGroup = $applicationGroup;
                break;
            }
        }

        if (!empty($validApplicationGroup)) {
//<xs:element name="ProtocolloDomanda" type="xs:string" />
            $ProtocolloDomanda = $this->checkFieldString($validApplicationGroup->getProtocolNumber());
            if (!empty($ProtocolloDomanda)) {
                $resultConfidi['ProtocolloDomanda'] = $ProtocolloDomanda;
            }
//<xs:element name="DataPresentazione" type="xs:date" />
            $DataPresentazione = $this->checkFieldDate($validApplicationGroup->getProtocolDate());
            if (!empty($DataPresentazione)) {
                $resultConfidi['DataPresentazione'] = $DataPresentazione;
            }
        }

// totale riassicurazioni (anche se più di una domanda): setto predefinito 0 e poi aggiorno contatore
        $resultConfidi['NumeroRiassicurazioni'] = $this->numeroRiassicurazioni;

//totale richieste contributi (anche se più di una domanda): setto predefinito 0 e poi aggiorno contatore
        $resultConfidi['NumeroContributiAggiuntivi'] = $this->numeroContributiAggiuntivi;

//TODO: [???]
//TODO: verificare valorizzazione
// totale richieste attestazioni (anche se più di una domanda): setto predefinito 0 e poi aggiorno contatore
        $resultConfidi['NumeroAttestazioni'] = $this->numeroAttestazioni;

        foreach($applicationGroupList as $applicationGroup) {
//solo application di applicationGroup non ancora inviate
            $criteria = [];
            $criteria['applicationGroup'] = $applicationGroup;
            $applicationList = $this->entityManager->getRepository(Application::class)->findAllForNsia($criteria);
//dd(count($applicationList));
//print_r(count($applicationList));

            foreach ($applicationList as $application) {
//<xs:element maxOccurs="unbounded" minOccurs="1" name="Riassicurazione">
                if (!$application->getRegistryFileAudit()) {
                    $resultRiassicurazione = $this->createRiassicurazioneData($application);
                    if (!empty($resultRiassicurazione)) {
                        $resultConfidi['Riassicurazione'][] = $resultRiassicurazione;
                        $this->numeroRiassicurazioni++;
                    }
                }
//dd(count($application->getAdditionalContributions()));

                $resultContributoAggiuntivo = $this->createContributoAggiuntivoData($application);
                if (!empty($resultContributoAggiuntivo)) {
                    foreach ($resultContributoAggiuntivo as $item) {
                        $resultConfidi['ContributoAggiuntivo'][] = $item;
                        $this->numeroContributiAggiuntivi++;
                    }
                }

//TODO: [???]
//TODO: verificare valorizzazione
                $resultAttestazioneErogazione = $this->createAttestazioneErogazioneData($application);
                if (!empty($resultAttestazioneErogazione)) {
                    foreach ($resultAttestazioneErogazione as $item) {
                        $resultConfidi['AttestazioneErogazione'][] = $item;
                        $this->numeroAttestazioni++;
                    }
                }
            }

// totale riassicurazioni (anche se più di una domanda): aggiorno contatore
//<xs:element name="NumeroRiassicurazioni" type="xs:int" />
            $resultConfidi['NumeroRiassicurazioni'] = $this->numeroRiassicurazioni;

// totale richieste contributi (anche se più di una domanda): aggiorno contatore
//<xs:element name="NumeroContributiAggiuntivi" type="xs:int" />
            $resultConfidi['NumeroContributiAggiuntivi'] = $this->numeroContributiAggiuntivi;

//TODO: [???]
//TODO: verificare valorizzazione
// totale richieste attestazioni (anche se più di una domanda): setto predefinito 0 e poi aggiorno contatore
//<xs:element name="NumeroAttestazioni" type="xs:int" />
                $resultConfidi['NumeroAttestazioni'] = $this->numeroAttestazioni;

        }
        return $resultConfidi;
    }

    private function createRiassicurazioneData(Application $application = null)
    {
		$resultRiassicurazione = [];

//      <xs:element name="CodicePraticaWEB" type="xs:int" />
        $CodicePraticaWEB = $this->checkFieldInteger($application->getId());
        if (!empty($CodicePraticaWEB)) {
            $resultRiassicurazione['CodicePraticaWEB'] = $CodicePraticaWEB;
        }
//      <xs:element name="CodicePraticaConfidi" type="String20" />
        $CodicePraticaConfidi = $this->checkFieldString($application->getPracticeId(), 20);
        if (!empty($CodicePraticaConfidi)) {
            $resultRiassicurazione['CodicePraticaConfidi'] = $CodicePraticaConfidi;
        }
//      <xs:element name="FlagEnergia" type="Character" /> <!-- OBBLIGATORIO; valori ammessi S=Energia Sì, N=Energia No-->
        $FlagEnergia = $this->checkFieldString($application->getFlagEnergia(), 1);
        if (!empty($FlagEnergia)) {
            $resultRiassicurazione['FlagEnergia'] = $FlagEnergia;
        }
//      <xs:element name="TipoFinanziamento" type="Character" /> <!-- valori ammessi F = Finanziamento, L = Leasing-->
// in base a info finanziamento / leasing: setto predefinito null e poi aggiorno
        $TipoFinanziamento = null;
        $resultRiassicurazione['TipoFinanziamento'] = $TipoFinanziamento;

//      <xs:element maxOccurs="1" minOccurs="0" name="Finanziamento">
        $Finanziamento = $this->getFinanziamentoInfo ($application);
        if (!empty($Finanziamento)) {
            $resultRiassicurazione['Finanziamento'][] = $Finanziamento;
            $TipoFinanziamento = 'F';
        }
//      <xs:element maxOccurs="1" minOccurs="0" name="Leasing">
        $Leasing = $this->getLeasingInfo ($application);
        if (!empty($Leasing)) {
            $resultRiassicurazione['Leasing'][] = $Leasing;
            $TipoFinanziamento = 'L';
        }

// aggiorno tipo finaniamento in base a richiesta
        if ($TipoFinanziamento) {
            $resultRiassicurazione['TipoFinanziamento'] = $TipoFinanziamento;
        } else {
            unset($resultRiassicurazione['TipoFinanziamento']);
        }

//      <xs:element name="ImportoGarantito" type="Importo" /> <xs:restriction base="xs:decimal"> <xs:totalDigits value="13"/><xs:fractionDigits value="2"/>
        $ImportoGarantito = $this->checkFieldDecimal($application->getAeGAssuranceAmount(), 13);
        if (!empty($ImportoGarantito)) {
            $resultRiassicurazione['ImportoGarantito'] = $ImportoGarantito;
        }
//      <xs:element name="DataDeliberaConfidi" type="xs:date" />
        $DataDeliberaConfidi = $this->checkFieldDate($application->getAeGResolutionDate());
        if (!empty($DataDeliberaConfidi)) {
            $resultRiassicurazione['DataDeliberaConfidi'] = $DataDeliberaConfidi;
        }

//      <xs:element name="RichiestoAbbuono" type="Character" />   <!-- valori S o N -->
        $RichiestoAbbuono = $this->checkFieldString($application->getAeAcCommissionsRebateRequest(), 1);
        if (!empty($RichiestoAbbuono)) {
            $resultRiassicurazione['RichiestoAbbuono'] = $RichiestoAbbuono;
        }
//      <xs:element name="RichiestoContributo" type="Character" />     <!-- valori S o N -->
        $RichiestoContributo = $this->checkFieldString($application->getAeAcInterestsContributionRequest(), 1);
        if (!empty($RichiestoContributo)) {
            $resultRiassicurazione['RichiestoContributo'] = $RichiestoContributo;
        }
//      <xs:element name="RichiestoFondoPerduto" type="Character" /> <!-- valori S o N -->
        $RichiestoFondoPerduto = $this->checkFieldString($application->getAeAcLostFundContributionRequest(), 1);
        if (!empty($RichiestoFondoPerduto)) {
            $resultRiassicurazione['RichiestoFondoPerduto'] = $RichiestoFondoPerduto;
        }
//      <xs:element name="ImportoDomandaContributo" type="Importo" /> <xs:restriction base="xs:decimal"> <xs:totalDigits value="13"/><xs:fractionDigits value="2"/>
        $ImportoDomandaContributo = $this->checkFieldDecimal($application->getAeAcContributionApplicationAmount(), 13);
        if (!empty($ImportoDomandaContributo) && $ImportoDomandaContributo != '0.00') {
            $resultRiassicurazione['ImportoDomandaContributo'] = $ImportoDomandaContributo;
        }
////      <xs:element name="NumeroSociPartecipanti" type="xs:int" /> solo per cooperative??
//        $NumeroSociPartecipanti = $this->checkFieldInteger($application->getAeAcApplicationMembers());
//        if (!empty($NumeroSociPartecipanti)) {
//            $resultRiassicurazione['NumeroSociPartecipanti'] = $NumeroSociPartecipanti;
//        }
//      <xs:element maxOccurs="1" minOccurs="1" name="Impresa">
        $Impresa = $this->getImpresaInfo($application);
        if (!empty($Impresa)) {
            $resultRiassicurazione['Impresa'][] = $Impresa;
        }

        return $resultRiassicurazione;
    }

    private function createContributoAggiuntivoData(Application $application = null)
    {
        $criteria = [];
        $criteria['application'] = $application;
        $additionalContributionList = $this->entityManager->getRepository(AdditionalContribution::class)->findAllForNsia($criteria);
        $has_CON = array_filter($additionalContributionList, function($additionalContributionList){
            return $additionalContributionList->getType() == AdditionalContribution::TYPE_CON;
        });
        $has_CFP = array_filter($additionalContributionList, function($additionalContributionList){
            return $additionalContributionList->getType() == AdditionalContribution::TYPE_CFP;
        });

        $ContributiAggiuntivi = [];
        foreach($additionalContributionList as $additionalContribution) {
            if ($has_CON && $has_CFP && $additionalContribution->getType() == AdditionalContribution::TYPE_CON) {
                continue;
            }
            $ContributiAggiuntivi[] = $this->getContributoAggiuntivo($additionalContribution);
        }

        return $ContributiAggiuntivi;
    }

//TODO: [???]
//TODO: verificare valorizzazione
    private function createAttestazioneErogazioneData(Application $application = null)
    {
//TODO: solo attestazioni di application non ancora inviate
        $AttestazioniErogazioni = [];

        $financingProvisioningCertification = $application->getFinancingProvisioningCertification();

        if (
//TODO: [???]
//TODO: verificare condizioni
            ($application->getFDfLoanProvidedAtImport() == 'N')
//            (empty($application->getFDfLoanProvidedAtImport()) || $application->getFDfLoanProvidedAtImport() == 'N')
            &&
            !empty($financingProvisioningCertification)
            &&
            empty($financingProvisioningCertification->getregistryFileAudit())
            &&
            ($financingProvisioningCertification->getStatus() == FinancingProvisioningCertification::STATUS_COMPLETED)
        ) {
            $AttestazioniErogazioni[] = $this->getAttestazioneErogazioneInfo($financingProvisioningCertification);
        }

        return $AttestazioniErogazioni;
    }



    private function getFinanziamentoInfo(Application $application): array
    {
//  <xs:element maxOccurs="1" minOccurs="0" name="Finanziamento">
        $Finanziamento = [];

//TODO: verificare controllo solo domande finanziamento presenti in import xls

//TODO: [???]
//TODO: verificare condizioni
//        if ($application->getFDfLoanProvidedAtImport() == 'S') {
//        if (empty($application->getFDfLoanProvidedAtImport()) || $application->getFDfLoanProvidedAtImport() == 'S') {

            $bank = $this->getApplicationBank($application);
//TODO: verifica compilazione $CodiceNSIABanca OR $RagioneSocialeBanca + $ABIBanca
//      <xs:element name="CodiceNSIABanca" type="String5" /> <!-- decodifica da Foglio Excel -->
            $CodiceNSIABanca = $this->checkFieldString($bank ? $bank->getCode() : null, 5);
            if (!empty($CodiceNSIABanca)) {
                $Finanziamento['CodiceNSIABanca'] = $CodiceNSIABanca;
            }
//      <xs:element name="RagioneSocialeBanca" type="xs:string" />
            $RagioneSocialeBanca = $this->checkFieldString($application->getFDbfBusinessName());
            if (!empty($RagioneSocialeBanca)) {
                $Finanziamento['RagioneSocialeBanca'] = $RagioneSocialeBanca;
            }
//      <xs:element name="ABIBanca" type="String5" />
            $ABIBanca = $this->checkFieldString($application->getFDbfABI(), 5);
            if (!empty($ABIBanca)) {
                $Finanziamento['ABIBanca'] = $ABIBanca;
            }
//      <xs:element name="Finalita" type="String5" /> <!-- decodifica da Foglio Excel -->
            $financialDestination = $this->getApplicationFinancialDestination($application);
            $Finalita = $this->checkFieldString($financialDestination ? $financialDestination->getCode() : null, 5);
            if (!empty($Finalita)) {
                $Finanziamento['Finalita'] = $Finalita;
            }
//      <xs:element name="ImportoFinanziamento" type="Importo" /> <xs:restriction base="xs:decimal"> <xs:totalDigits value="13"/><xs:fractionDigits value="2"/>
            $ImportoFinanziamento = $this->checkFieldDecimal($application->getFDfAmount(), 13);
            if (!empty($ImportoFinanziamento)) {
                $Finanziamento['ImportoFinanziamento'] = $ImportoFinanziamento;
            }
//TODO: [???]
//TODO: verificare valorizzazione
//        <xs:element name="FinanziamentoErogato" type="Character" /> <!--  valori S' o 'N' -->
            $FinanziamentoErogato = $this->checkFieldString($application->getFDfLoanProvidedAtImport(), 1);
            if (!empty($FinanziamentoErogato)) {
                $Finanziamento['FinanziamentoErogato'] = $FinanziamentoErogato;
            }
//      <xs:element name="DataFirmaContratto" type="xs:date" />
            $DataFirmaContratto = $this->checkFieldDate($application->getFDfContractSignatureDate());
            if (!empty($DataFirmaContratto)) {
                $Finanziamento['DataFirmaContratto'] = $DataFirmaContratto;
            }
//      <xs:element name="DataDeliberaBanca" type="xs:date" />
            $DataDeliberaBanca = $this->checkFieldDate($application->getFDfResolutionDate());
            if (!empty($DataDeliberaBanca)) {
                $Finanziamento['DataDeliberaBanca'] = $DataDeliberaBanca;
            }
//      <xs:element name="DataErogazione" type="xs:date" />
            $DataErogazione = $this->checkFieldDate($application->getFDfIssueDate());
            if (!empty($DataErogazione)) {
                $Finanziamento['DataErogazione'] = $DataErogazione;
            }
//      <xs:element name="DurataFinanziamento" type="xs:short" />
            $DurataFinanziamento = $this->checkFieldInteger($application->getFDfDuration());
            if (!empty($DurataFinanziamento)) {
                $Finanziamento['DurataFinanziamento'] = $DurataFinanziamento;
            }
//      <xs:element name="PeriodicitaRateAmmortamento" type="xs:short" />
            $periodicity = $this->getApplicationPeriodicityF($application);
            $PeriodicitaRateAmmortamento = $this->checkFieldInteger($periodicity ? $periodicity->getMonths() : null);
            if (!empty($PeriodicitaRateAmmortamento)) {
                $Finanziamento['PeriodicitaRateAmmortamento'] = $PeriodicitaRateAmmortamento;
            }
//      <xs:element name="DataScadenzaPrimaRata" type="xs:date" />
            $DataScadenzaPrimaRata = $this->checkFieldDate($application->getFDfFirstDepreciationDeadline());
            if (!empty($DataScadenzaPrimaRata)) {
                $Finanziamento['DataScadenzaPrimaRata'] = $DataScadenzaPrimaRata;
            }
//      <xs:element name="EsistePreammortamento" type="Character" /> <!--  valori S' o 'N' -->
            $EsistePreammortamento = $this->checkFieldString($application->getFDfPreDepreciationExists(), 1);
            if (!empty($EsistePreammortamento)) {
                $Finanziamento['EsistePreammortamento'] = $EsistePreammortamento;
            }
//      <xs:element name="ImportoRataAmmortamento" type="Importo" /> <xs:restriction base="xs:decimal"> <xs:totalDigits value="13"/><xs:fractionDigits value="2"/>
            $ImportoRataAmmortamento = $this->checkFieldDecimal($application->getFDfInstallmentAmount(), 13);
            if (!empty($ImportoRataAmmortamento)) {
                $Finanziamento['ImportoRataAmmortamento'] = $ImportoRataAmmortamento;
            }
//      <xs:element name="TipologiaTasso" type="Character" /> <!-- valori ammessi F=Fisso, V=Variabile -->
            $TipologiaTasso = $this->checkFieldString($application->getFTRateType(), 1);
            if (!empty($TipologiaTasso)) {
                $Finanziamento['TipologiaTasso'] = $TipologiaTasso;
            }
//      <xs:element name="TassoFinanziamento" type="Percentuale" /> <xs:restriction base="xs:decimal"> <xs:totalDigits value="8"/><xs:fractionDigits value="2"/>
            $TassoFinanziamento = $this->checkFieldDecimal($application->getFTRate(), 8);
            if (!empty($TassoFinanziamento)) {
                $Finanziamento['TassoFinanziamento'] = $TassoFinanziamento;
            }
//      <xs:element name="TAEG" type="Percentuale" /> <xs:restriction base="xs:decimal"> <xs:totalDigits value="8"/><xs:fractionDigits value="2"/>
            $TAEG = $this->checkFieldDecimal($application->getFTTaeg(), 8);
            if (!empty($TAEG)) {
                $Finanziamento['TAEG'] = $TAEG;
            }
//        }
        return $Finanziamento;
    }

    private function getLeasingInfo(Application $application): array
    {
//  <xs:element maxOccurs="1" minOccurs="0" name="Leasing">
        $Leasing = [];
//TODO: verifica compilazione $CodiceNSIABancaLeasing OR $RagioneSocialeBancaLeasing
//      <xs:element name="CodiceNSIABancaLeasing" type="String5" /> <!-- decodifica da Foglio Excel -->
        $bankLeasing = $this->getApplicationBankLeasing($application);
        $CodiceNSIABancaLeasing = $this->checkFieldString($bankLeasing ? $bankLeasing->getCode() : null, 5);
        if (!empty($CodiceNSIABancaLeasing)) {
            $Leasing['CodiceNSIABancaLeasing'] = $CodiceNSIABancaLeasing;
        }
//      <xs:element name="RagioneSocialeBancaLeasing" type="xs:string" />
        $RagioneSocialeBancaLeasing = $this->checkFieldString($application->getLSfBusinessName());
        if (!empty($RagioneSocialeBancaLeasing)) {
            $Leasing['RagioneSocialeBancaLeasing'] = $RagioneSocialeBancaLeasing;
        }
//      <xs:element name="Finalita" type="String5" /> <!-- decodifica da Foglio Excel -->
        $leasingDestination = $this->getApplicationLeasingDestination($application);
        $Finalita = $this->checkFieldString($leasingDestination ? $leasingDestination->getCode() : null, 5);
        if (!empty($Finalita)) {
            $Leasing['Finalita'] = $Finalita;
        }
//      <xs:element name="ImportoLeasing" type="Importo" /> <xs:restriction base="xs:decimal"> <xs:totalDigits value="13"/><xs:fractionDigits value="2"/>
        $ImportoLeasing = $this->checkFieldDecimal($application->getLDclAmount(), 13);
        if (!empty($ImportoLeasing)) {
            $Leasing['ImportoLeasing'] = $ImportoLeasing;
        }
//      <xs:element name="DataFirmaContratto" type="xs:date" />
        $DataFirmaContratto = $this->checkFieldDate($application->getLDclContractSignatureDate());
        if (!empty($DataFirmaContratto)) {
            $Leasing['DataFirmaContratto'] = $DataFirmaContratto;
        }
//      <xs:element name="DataSottoscrizioneVerbale" type="xs:date" />
        $DataSottoscrizioneVerbale = $this->checkFieldDate($application->getLDclResolutionDate());
        if (!empty($DataSottoscrizioneVerbale)) {
            $Leasing['DataSottoscrizioneVerbale'] = $DataSottoscrizioneVerbale;
        }
//      <xs:element name="DurataLeasing" type="xs:short" />
        $DurataLeasing = $this->checkFieldInteger($application->getLDclDuration());
        if (!empty($DurataLeasing)) {
            $Leasing['DurataLeasing'] = $DurataLeasing;
        }
//      <xs:element name="PeriodicitaCanoni" type="xs:short" />
        $periodicity = $this->getApplicationPeriodicityL($application);
        $PeriodicitaCanoni = $this->checkFieldInteger($periodicity ? $periodicity->getMonths() : null);
        if (!empty($PeriodicitaCanoni)) {
            $Leasing['PeriodicitaCanoni'] = $PeriodicitaCanoni;
        }
//      <xs:element name="DataScadenzaPrimoCanone" type="xs:date" />
        $DataScadenzaPrimoCanone = $this->checkFieldDate($application->getLDclFirstDeadline());
        if (!empty($DataScadenzaPrimoCanone)) {
            $Leasing['DataScadenzaPrimoCanone'] = $DataScadenzaPrimoCanone;
        }
//      <xs:element name="ImportoCanone" type="Importo" /> <xs:restriction base="xs:decimal"> <xs:totalDigits value="13"/><xs:fractionDigits value="2"/>
        $ImportoCanone = $this->checkFieldDecimal($application->getLDclFeeAmount(), 13);
        if (!empty($ImportoCanone)) {
            $Leasing['ImportoCanone'] = $ImportoCanone;
        }
//      <xs:element name="PercentualeMacroCanone" type="Percentuale" /> <xs:restriction base="xs:decimal"> <xs:totalDigits value="8"/><xs:fractionDigits value="2"/>
        $PercentualeMacroCanone = $this->checkFieldDecimal($application->getLDclFeePercentage(), 8);
        if (!empty($PercentualeMacroCanone)) {
            $Leasing['PercentualeMacroCanone'] = $PercentualeMacroCanone;
        }
//      <xs:element name="PercentualeRiscatto" type="Percentuale" /> <xs:restriction base="xs:decimal"> <xs:totalDigits value="8"/><xs:fractionDigits value="2"/>
        $PercentualeRiscatto = $this->checkFieldDecimal($application->getLDclRansomPercentage(), 8);
        if (!empty($PercentualeRiscatto)) {
            $Leasing['PercentualeRiscatto'] = $PercentualeRiscatto;
        }
//      <xs:element name="TassoLeasing" type="Percentuale" /> <xs:restriction base="xs:decimal"> <xs:totalDigits value="8"/><xs:fractionDigits value="2"/>
        $TassoLeasing = $this->checkFieldDecimal($application->getLDclRate(), 8);
        if (!empty($TassoLeasing)) {
            $Leasing['TassoLeasing'] = $TassoLeasing;
        }

        return $Leasing;
    }

//TODO: [???]
    private function getAttestazioneErogazioneInfo(FinancingProvisioningCertification $financingProvisioningCertification): array
    {
//<xs:element name="CodicePraticaWebRiass" type="xs:int" />
//<xs:element name="CodicePraticaNSIARiass" type="String10" />
//<xs:element name="DataPresentazioneAttestazione" type="xs:date" />

//<xs:element name="ImportoFinanziamento" type="Importo" />
//<xs:element name="DataFirmaContratto" type="xs:date" />
//<xs:element name="DataErogazione" type="xs:date" />
//<xs:element name="PeriodicitaRateAmmortamento" type="xs:short" />
//<xs:element name="DataScadenzaPrimaRata" type="xs:date" />
//<xs:element name="EsistePreammortamento" type="Character" />  <!--  valori S' o 'N' -->
//<xs:element name="ImportoRataAmmortamento" type="Importo" />
//<xs:element name="TipologiaTasso" type="Character" />  <!-- valori ammessi F=Fisso, V=Variabile -->
//<xs:element name="TassoFinanziamento" type="Percentuale" />
//<xs:element name="TAEG" type="Percentuale" />
//<xs:element name="Spread" type="Percentuale" />

        $application = $financingProvisioningCertification->getApplication();


        $AttestazioneErogazione = [];

//<xs:element name="CodicePraticaWEBRiass" type="xs:int" />
        $CodicePraticaWEBRiass = $this->checkFieldInteger($application->getId());
        if (!empty($CodicePraticaWEBRiass)) {
            $AttestazioneErogazione['CodicePraticaWEBRiass'] = $CodicePraticaWEBRiass;
        }

//TODO: [???]
// practice id / nsia numero posizione
//        $application->getPracticeId();
//        $application->getNsiaNumeroPosizione();
//<xs:element name="CodicePraticaNSIARiass" type="xs:int" />
        $CodicePraticaNSIARiass = $this->checkFieldInteger($application->getNsiaNumeroPosizione());
        if (!empty($CodicePraticaNSIARiass)) {
            $AttestazioneErogazione['CodicePraticaNSIARiass'] = $CodicePraticaNSIARiass;
        }

//TODO: [???]
// data creazione / salvataggio pdf
//<xs:element name="DataPresentazioneAttestazione" type="xs:date" />
//        $DataPresentazioneAttestazione = $this->checkFieldDate($financingProvisioningCertification->getIssueDate());
//        $DataPresentazioneAttestazione = $this->checkFieldDate($financingProvisioningCertification->getContractSignatureDate());
        $DataPresentazioneAttestazione = $this->checkFieldDate($financingProvisioningCertification->getFileUploadedAt());
        if (!empty($DataPresentazioneAttestazione)) {
            $AttestazioneErogazione['DataPresentazioneAttestazione'] = $DataPresentazioneAttestazione;
        }

        $Finanziamento = [];
//      <xs:element name="ImportoFinanziamento" type="Importo" /> <xs:restriction base="xs:decimal"> <xs:totalDigits value="13"/><xs:fractionDigits value="2"/>
//        $ImportoFinanziamento = $this->checkFieldDecimal($application->getFDfAmount(), 13);
        $ImportoFinanziamento = $this->checkFieldDecimal($financingProvisioningCertification->getAmount(), 13);
        if (!empty($ImportoFinanziamento)) {
//            $AttestazioneErogazione['ImportoFinanziamento'] = $ImportoFinanziamento;
            $Finanziamento['ImportoFinanziamento'] = $ImportoFinanziamento;
        }

//      <xs:element name="DataFirmaContratto" type="xs:date" />
//        $DataFirmaContratto = $this->checkFieldDate($application->getFDfContractSignatureDate());
        $DataFirmaContratto = $this->checkFieldDate($financingProvisioningCertification->getContractSignatureDate());
        if (!empty($DataFirmaContratto)) {
//            $AttestazioneErogazione['DataFirmaContratto'] = $DataFirmaContratto;
            $Finanziamento['DataFirmaContratto'] = $DataFirmaContratto;
        }
//      <xs:element name="DataDeliberaBanca" type="xs:date" />
//        $DataDeliberaBanca = $this->checkFieldDate($application->getFDfResolutionDate());
//        if (!empty($DataDeliberaBanca)) {
//            $Finanziamento['DataDeliberaBanca'] = $DataDeliberaBanca;
//        }
//      <xs:element name="DataErogazione" type="xs:date" />
        $DataErogazione = $this->checkFieldDate($financingProvisioningCertification->getIssueDate());
        if (!empty($DataErogazione)) {
//            $AttestazioneErogazione['DataErogazione'] = $DataErogazione;
            $Finanziamento['DataErogazione'] = $DataErogazione;
        }
//      <xs:element name="DurataFinanziamento" type="xs:short" />
       $DurataFinanziamento = $this->checkFieldInteger($financingProvisioningCertification->getFinancingDuration());
        if (!empty($DurataFinanziamento)) {
//            $AttestazioneErogazione['DurataFinanziamento'] = $DurataFinanziamento;
            $Finanziamento['DurataFinanziamento'] = $DurataFinanziamento;
        }
//      <xs:element name="PeriodicitaRateAmmortamento" type="xs:short" />
        $periodicity = $this->getFinancingProvisioningCertificationPeriodicity($application);
        $PeriodicitaRateAmmortamento = $this->checkFieldInteger($periodicity ? $periodicity->getMonths() : null);
        if (!empty($PeriodicitaRateAmmortamento)) {
//            $AttestazioneErogazione['PeriodicitaRateAmmortamento'] = $PeriodicitaRateAmmortamento;
            $Finanziamento['PeriodicitaRateAmmortamento'] = $PeriodicitaRateAmmortamento;
        }
//      <xs:element name="DataScadenzaPrimaRata" type="xs:date" />
        $DataScadenzaPrimaRata = $this->checkFieldDate($financingProvisioningCertification->getFirstDepreciationDeadline());
        if (!empty($DataScadenzaPrimaRata)) {
//            $AttestazioneErogazione['DataScadenzaPrimaRata'] = $DataScadenzaPrimaRata;
            $Finanziamento['DataScadenzaPrimaRata'] = $DataScadenzaPrimaRata;
        }
//      <xs:element name="EsistePreammortamento" type="Character" /> <!--  valori S' o 'N' -->
//        $EsistePreammortamento = $this->checkFieldString($financingProvisioningCertification->getPreDepreciationExists(), 1);
        $EsistePreammortamento = $this->checkFieldInteger($financingProvisioningCertification->getPreDepreciation()) ? 'S' : 'N';
        if (!empty($EsistePreammortamento)) {
//            $AttestazioneErogazione['EsistePreammortamento'] = $EsistePreammortamento;
            $Finanziamento['EsistePreammortamento'] = $EsistePreammortamento;
        }
//      <xs:element name="ImportoRataAmmortamento" type="Importo" /> <xs:restriction base="xs:decimal"> <xs:totalDigits value="13"/><xs:fractionDigits value="2"/>
        $ImportoRataAmmortamento = $this->checkFieldDecimal($financingProvisioningCertification->getInstallmentAmount(), 13);
        if (!empty($ImportoRataAmmortamento)) {
//            $AttestazioneErogazione['ImportoRataAmmortamento'] = $ImportoRataAmmortamento;
            $Finanziamento['ImportoRataAmmortamento'] = $ImportoRataAmmortamento;
        }
//      <xs:element name="TipologiaTasso" type="Character" /> <!-- valori ammessi F=Fisso, V=Variabile -->
        $TipologiaTasso = $this->checkFieldString($financingProvisioningCertification->getRateType(), 1);
        if (!empty($TipologiaTasso)) {
//            $AttestazioneErogazione['TipologiaTasso'] = $TipologiaTasso;
            $Finanziamento['TipologiaTasso'] = $TipologiaTasso;
        }
//      <xs:element name="TassoFinanziamento" type="Percentuale" /> <xs:restriction base="xs:decimal"> <xs:totalDigits value="8"/><xs:fractionDigits value="2"/>
        $TassoFinanziamento = $this->checkFieldDecimal($financingProvisioningCertification->getRate(), 8);
        if (!empty($TassoFinanziamento)) {
//            $AttestazioneErogazione['TassoFinanziamento'] = $TassoFinanziamento;
            $Finanziamento['TassoFinanziamento'] = $TassoFinanziamento;
        }
//      <xs:element name="TAEG" type="Percentuale" /> <xs:restriction base="xs:decimal"> <xs:totalDigits value="8"/><xs:fractionDigits value="2"/>
        $TAEG = $this->checkFieldDecimal($financingProvisioningCertification->getTaeg(), 8);
        if (!empty($TAEG)) {
//            $AttestazioneErogazione['TAEG'] = $TAEG;
            $Finanziamento['TAEG'] = $TAEG;
        }

//TODO: [???]
//TODO: verificare valorizzazione
//      <xs:element name="Spread" type="Percentuale" /> <xs:restriction base="xs:decimal"> <xs:totalDigits value="8"/><xs:fractionDigits value="2"/>
        $Spread = $this->checkFieldDecimal($financingProvisioningCertification->getSpread(), 8);
        if (!empty($Spread)) {
//            $AttestazioneErogazione['Spread'] = $Spread;
            $Finanziamento['Spread'] = $Spread;
        }

        $AttestazioneErogazione['Finanziamento'][] = $Finanziamento;

        return $AttestazioneErogazione;
    }

    private function getImpresaInfo(Application $application): array
    {
//  <xs:element maxOccurs="1" minOccurs="1" name="Impresa">
        $Impresa = [];

//      <xs:element name="CodiceFiscale" type="String16" />
        $CodiceFiscale = $this->checkFieldString($application->getAeIbFiscalCode(), 16);
        if (!empty($CodiceFiscale)) {
            $Impresa['CodiceFiscale'] = $CodiceFiscale;
        }
//      <xs:element name="RagioneSociale" type="String60" />
        $RagioneSociale = $this->checkFieldString($application->getAeIbBusinessName(), 60);
        if (!empty($RagioneSociale)) {
            $Impresa['RagioneSociale'] = $RagioneSociale;
        }
//      <xs:element name="DataCostituzione" type="xs:date" />
        $DataCostituzione = $this->checkFieldDate($application->getAeIbConstitutionDate());
        if (!empty($DataCostituzione)) {
            $Impresa['DataCostituzione'] = $DataCostituzione;
        }

//      <xs:element name="FormaGiuridica" type="String5" />   <!-- Decodifica da Foglio Excel -->
        $legalForm = $this->getApplicationLegalForm($application);
        $FormaGiuridica = $this->checkFieldString(($legalForm) ? $legalForm->getReferenceId() : null, 5);
        if (!empty($FormaGiuridica)) {
            $Impresa['FormaGiuridica'] = $FormaGiuridica;
        }
//<xs:element name="DimensioneImpresa" type="xs:string" /> <!--MI = micro, PI = piccola, 'ME = media -->
        $companySize = $this->getApplicationCompanySize($application);
        $DimensioneImpresa = $this->checkFieldString(($companySize) ? $companySize->getCode() : null, 2);
//TODO: deve esserci sempre, verificare valorizzaizone
        if (!empty($DimensioneImpresa)) {
            $Impresa['DimensioneImpresa'] = $DimensioneImpresa;
        }
//      <xs:element name="CodiceCCIA" type="String10" />
        $CodiceCCIA = $this->checkFieldString($application->getAeIbChamberOfCommerceCode(), 10);
        if (!empty($CodiceCCIA)) {
            $Impresa['CodiceCCIA'] = $CodiceCCIA;
        }
//      <xs:element name="DataIscrizioneCCIA" type="xs:date" />
        $DataIscrizioneCCIA = $this->checkFieldDate($application->getAeIbChamberOfCommerceRegistrationDate());
        if (!empty($DataIscrizioneCCIA)) {
            $Impresa['DataIscrizioneCCIA'] = $DataIscrizioneCCIA;
        }
//      <xs:element name="CodiceAIA" type="String10" />
        $CodiceAIA = $this->checkFieldString($application->getAeIbAIACode(), 10);
        if (!empty($CodiceAIA)) {
            $Impresa['CodiceAIA'] = $CodiceAIA;
        }
//      <xs:element name="DataIscrizioneAIA" type="xs:date" />
        $DataIscrizioneAIA = $this->checkFieldDate($application->getAeIbAIARegistrationDate());
        if (!empty($DataIscrizioneAIA)) {
            $Impresa['DataIscrizioneAIA'] = $DataIscrizioneAIA;
        }
//      <xs:element name="CodiceAttivitaATECO" type="String10" />
        $CodiceAttivitaATECO = $this->checkFieldString($application->getAeIbAtecoCode(), 10);
        if (!empty($CodiceAttivitaATECO)) {
            $Impresa['CodiceAttivitaATECO'] = $CodiceAttivitaATECO;
        }
//      <xs:element name="DataInizioAttivitaATECO" type="xs:date" />
        $DataInizioAttivitaATECO = $this->checkFieldDate($application->getAeIbAtecoStartDate());
        if (!empty($DataInizioAttivitaATECO)) {
            $Impresa['DataInizioAttivitaATECO'] = $DataInizioAttivitaATECO;
        }
//      <xs:element name="CodiceIBAN" type="xs:string" />
        $CodiceIBAN = $this->checkFieldString($application->getAeIbIban());
        if (!empty($CodiceIBAN)) {
            $Impresa['CodiceIBAN'] = $CodiceIBAN;
        }
//      <xs:element name="IndirizzoEMAIL" type="String60" />
        $IndirizzoEMAIL = $this->checkFieldString($application->getAePecAddress(), 60);
        if (!empty($IndirizzoEMAIL)) {
            $Impresa['IndirizzoEMAIL'] = $IndirizzoEMAIL;
        }
//      <xs:element name="IndirizzoSedeLegale" type="Indirizzo" />
        $IndirizzoSedeLegale = $this->getIndirizzoSedeLegale($application);
        if (!empty($IndirizzoSedeLegale)) {
            $Impresa['IndirizzoSedeLegale'] = $IndirizzoSedeLegale;
        }
//      <xs:element name="IndirizzoSedeOperativa" type="Indirizzo" />
        $IndirizzoSedeOperativa = $this->getIndirizzoSedeOperativa($application);
        if (!empty($IndirizzoSedeOperativa)) {
            $Impresa['IndirizzoSedeOperativa'] = $IndirizzoSedeOperativa;
        }
//      <xs:element name="TitolareImpresa" type="PersonaFisica" />
        $TitolareImpresa = $this->getPersonInfo($application, 'Owner');
        if (!empty($TitolareImpresa)) {
            $Impresa['TitolareImpresa'] = $TitolareImpresa;
        }
//      <xs:element name="DataAggregazioneTitolare" type="xs:date" />
        $DataAggregazioneTitolare = $this->getPersonAggregation($application, 'Owner');
        if (!empty($DataAggregazioneTitolare)) {
            $Impresa['DataAggregazioneTitolare'] = $DataAggregazioneTitolare;
        }

//      <xs:element maxOccurs="unbounded" minOccurs="0" name="SocioImpresa">
        $SocioImpresa = $this->getSocioImpresa($application);
        if (!empty($SocioImpresa)) {
            $Impresa['SocioImpresa'] = $SocioImpresa;
        }
        return $Impresa;
    }

	private function getIndirizzoSedeLegale(Application $application): array
    {
//<xs:complexType name="Indirizzo">
        $IndirizzoSedeLegale = [];
//<xs:element name="IndirizzoECivico" type="String60" />
        $IndirizzoECivico = $this->checkFieldString($application->getAeOfficeAddress(), 60);
        if (!empty($IndirizzoECivico)) {
            $IndirizzoSedeLegale['IndirizzoECivico'] = $IndirizzoECivico;
        }
//<xs:element name="Localita" type="String60" />
//        $Localita = $this->checkFieldString($application->getAeOfficeCity(), 60);
//        if (!empty($Localita)) {
//            $IndirizzoSedeLegale['Localita'] = $Localita;
//        }
//<xs:element name="CAP" type="String5" />
        $CAP = $this->checkFieldString($application->getAeOfficePostcode(), 5);
        if (!empty($CAP)) {
            $IndirizzoSedeLegale['CAP'] = $CAP;
        }
//<xs:element name="CodiceFiscaleComune" type="String4" />
        $city = $this->getCityByApplicationAndName($application, $application->getAeOfficeCity());
        $CodiceFiscaleComune = $this->checkFieldString($city ? $city->getCode() : null, 4);
        if (!empty($CodiceFiscaleComune)) {
            $IndirizzoSedeLegale['CodiceFiscaleComune'] = $CodiceFiscaleComune;
        }

        return $IndirizzoSedeLegale;
    }

	private function getIndirizzoSedeOperativa(Application $application): array
    {
//<xs:complexType name="Indirizzo">
        $IndirizzoSedeOperativa = [];
//<xs:element name="IndirizzoECivico" type="String60" />
        $IndirizzoECivico = $this->checkFieldString($application->getAeWorkplaceAddress(), 60);
        if (!empty($IndirizzoECivico)) {
            $IndirizzoSedeOperativa['IndirizzoECivico'] = $IndirizzoECivico;
        }
//<xs:element name="Localita" type="String60" />
//        $Localita = $this->checkFieldString($application->getAeWorkplaceCity(), 60);
//        if (!empty($Localita)) {
//            $IndirizzoSedeOperativa['Localita'] = $Localita;
//        }
//<xs:element name="CAP" type="String5" />
        $CAP = $this->checkFieldString($application->getAeWorkplacePostcode(), 5);
        if (!empty($CAP)) {
            $IndirizzoSedeOperativa['CAP'] = $CAP;
        }
//<xs:element name="CodiceFiscaleComune" type="String4" />
        $city = $this->getCityByApplicationAndName($application, $application->getAeWorkplaceCity());
        $CodiceFiscaleComune = $this->checkFieldString($city ? $city->getCode() : null, 4);
        if (!empty($CodiceFiscaleComune)) {
            $IndirizzoSedeOperativa['CodiceFiscaleComune'] = $CodiceFiscaleComune;
        }

        return $IndirizzoSedeOperativa;
    }

	private function getSocioImpresa(Application $application): array
    {
//<xs:element name="Socio" type="PersonaFisica" /> <!-- passare solo le persone fisiche -->

//<xs:complexType name="PersonaFisica">
        $SocioImpresa = [];

        $counter = 0;
        $PersonInfo = $this->getPersonInfo($application, 'FirstMember');
        $PersonAggregation = $this->getPersonAggregation($application, 'FirstMember');
        if (!empty($PersonInfo)) {
            $SocioImpresa[$counter]['Socio'] = $PersonInfo;
            if (!empty($PersonAggregation)) {
                $SocioImpresa[$counter]['DataAggregazione'] = $PersonAggregation;
            }
            $counter ++;
        }

        $PersonInfo = $this->getPersonInfo($application, 'SecondMember');
        $PersonAggregation = $this->getPersonAggregation($application, 'FirstMember');
        if (!empty($PersonInfo)) {
            $SocioImpresa[$counter]['Socio'] = $PersonInfo;
            if (!empty($PersonAggregation)) {
                $SocioImpresa[$counter]['DataAggregazione'] = $PersonAggregation;
            }
            $counter ++;
        }

        $PersonInfo = $this->getPersonInfo($application, 'ThirdMember');
        $PersonAggregation = $this->getPersonAggregation($application, 'FirstMember');
        if (!empty($PersonInfo)) {
            $SocioImpresa[$counter]['Socio'] = $PersonInfo;
            if (!empty($PersonAggregation)) {
                $SocioImpresa[$counter]['DataAggregazione'] = $PersonAggregation;
            }
            $counter ++;
        }

        $PersonInfo = $this->getPersonInfo($application, 'FourthMember');
        $PersonAggregation = $this->getPersonAggregation($application, 'FourthMember');
        if (!empty($PersonInfo)) {
            $SocioImpresa[$counter]['Socio'] = $PersonInfo;
            if (!empty($PersonAggregation)) {
                $SocioImpresa[$counter]['DataAggregazione'] = $PersonAggregation;
            }
            $counter ++;
        }

        return $SocioImpresa;
    }

    private function getPersonInfo(Application $application, $type = null): array
    {
//<xs:element name="Socio" type="PersonaFisica" /> <!-- passare solo le persone fisiche -->
        $PersonInfo = [];
        if ($type) {
            $methodCtrl = 'getAe' . $type . 'Lastname';
            if ($application->$methodCtrl()) {
//<xs:element name="Cognome" type="String60" />
                $methodName = 'getAe' . $type . 'Lastname';
                $Cognome = $this->checkFieldString($application->$methodName(), 60);
                if (!empty($Cognome)) {
                    $PersonInfo['Cognome'] = $Cognome;
                }
//<xs:element name="Nome" type="String60" />
                $methodName = 'getAe' . $type . 'Firstname';
                $Nome = $this->checkFieldString($application->$methodName(), 60);
                if (!empty($Nome)) {
                    $PersonInfo['Nome'] = $Nome;
                }
//<xs:element name="DataNascita" type="xs:date" />
                $methodName = 'getAe' . $type . 'BirthDate';
                $DataNascita = $this->checkFieldDate($application->$methodName());
                if (!empty($DataNascita)) {
                    $PersonInfo['DataNascita'] = $DataNascita;
                }
//<xs:element name="Sesso" type="Character"/>
                $methodName = 'getAe' . $type . 'Gender';
                $Sesso = $this->checkFieldString($application->$methodName(), 1);
                if (!empty($Sesso)) {
                    $PersonInfo['Sesso'] = $Sesso;
                }
//<xs:element name="CodiceFiscaleComuneNascita" type="String4" />
                $methodName = 'getAe' . $type . 'BirthCountry';
                $country_name = $application->$methodName();

//TODO: in caso di nascita stato estero vedi country member_birth_country
// [substr ($memberPhysicalPerson->getFiscalCode(), -5, 4)]
                $CodiceFiscaleComuneNascita = null;
                if($country_name) {
                    $methodName = 'getAe' . $type . 'FiscalCode';
                    $fiscalCode = $application->$methodName();
                    if ($fiscalCode) {
                        $CodiceFiscaleComuneNascita = substr ($application->$methodName(), -5, 4);
                    }
                }
                if(!$CodiceFiscaleComuneNascita) {
                    $methodName = 'getAe' . $type . 'BirthCity';
                    $city_name = $application->$methodName();
                    $city = $this->getCityByApplicationAndName($application, $city_name);
                    $CodiceFiscaleComuneNascita = $this->checkFieldString($city ? $city->getCode() : null, 4); //[TODO]
                }
                if (!empty($CodiceFiscaleComuneNascita)) {
                    $PersonInfo['CodiceFiscaleComuneNascita'] = $CodiceFiscaleComuneNascita;
                }
//<xs:element name="CodiceFiscale" type="String16" />
                $methodName = 'getAe' . $type . 'FiscalCode';
                $CodiceFiscale = $this->checkFieldString($application->$methodName(), 16);
                if (!empty($CodiceFiscale)) {
                    $PersonInfo['CodiceFiscale'] = $CodiceFiscale;
                }
            }
        }
        return $PersonInfo;
    }

    private function getPersonAggregation(Application $application, $type = null): ?string
    {
        $DataAggregazione = null;
        if ($type) {
            $methodName = 'getAe'.$type.'JoinDate';
            $DataAggregazione = $this->checkFieldDate($application->$methodName());
        }
        return $DataAggregazione;
    }

    private function getContributoAggiuntivo(AdditionalContribution $additionalContribution): array
    {
        $ContributoAggiuntivo = [];

//<xs:sequence>
//<!-- Il campo seguente va valorizzato con "ABB" per Abbuono commissioni, "CON" per
//contributo in conto/Interressi o Canoni, e "CFP" per Contributo in C/Imteressi o Canoni + Fondo Perduto  -->
//<xs:element name="TipoContributo" type="xs:string" />
        $TipoContributo = $this->checkFieldString($additionalContribution->getType());
        if (!empty($TipoContributo)) {
            $ContributoAggiuntivo['TipoContributo'] = $TipoContributo;
        }
//<xs:element name="CodicePraticaWEBRiass" type="xs:int" />
        $CodicePraticaWEBRiass = $this->checkFieldInteger($additionalContribution->getApplication()->getId());
        if (!empty($CodicePraticaWEBRiass)) {
            $ContributoAggiuntivo['CodicePraticaWEBRiass'] = $CodicePraticaWEBRiass;
        }
//[TODO] - serve?
//<xs:element name="ProtocolloWEB" type="String20" />
//        $ProtocolloWEB = $this->checkFieldString('', 20);
//        if (!empty($ProtocolloWEB)) {
//            $ContributoAggiuntivo['ProtocolloWEB'] = $ProtocolloWEB;
//        }
//<xs:element name="DataPresentazioneDomanda" type="xs:date" />
        $DataPresentazioneDomanda = $this->checkFieldDate($additionalContribution->getPresentationDate());
        if (!empty($DataPresentazioneDomanda)) {
            $ContributoAggiuntivo['DataPresentazioneDomanda'] = $DataPresentazioneDomanda;
        }
//</xs:sequence>

        return $ContributoAggiuntivo;
    }

}
