<?php

namespace App\Entity;

use App\Repository\ApplicationRepository;
use App\Entity\ApplicationMessage;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=ApplicationRepository::class)
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true, hardDelete=false)
 */
class Application
{
    const STATUS_CREATED = 'created';
    const STATUS_LINKED = 'linked';
    const STATUS_REGISTERED = 'registered';
    const STATUS_NSIA_00100 = '00100';
    const STATUS_NSIA_00101 = '00101';
    const STATUS_NSIA_00102 = '00102';
    const STATUS_NSIA_00103 = '00103';
    const STATUS_NSIA_00104 = '00104';
const STATUS_NSIA_00111 = '00111';
const STATUS_NSIA_00110 = '00110';
    const STATUS_NSIA_00105 = '00105';
    const STATUS_NSIA_00106 = '00106';
    const STATUS_NSIA_00107 = '00107';
    const STATUS_NSIA_00108 = '00108';
    const STATUS_NSIA_00109 = '00109';
//    const STATUS_NSIA_00110 = '00110';
//    const STATUS_NSIA_00111 = '00111';
    const STATUS_NSIA_00200 = '00200';
    const STATUS_NSIA_00201 = '00201';
    const STATUS_NSIA_00202 = '00202';
    const STATUS_NSIA_00205 = '00205';
    const STATUS_NSIA_00206 = '00206';
    const STATUS_NSIA_00207 = '00207';

    const INQUEST_STATUS_INTEGRATION_REQUESTED = 'integration_requested';
    const INQUEST_STATUS_INTEGRATION_SUPPLIED = 'integration_supplied';
    const INQUEST_STATUS_CLOSED = 'closed';

    const CONTRACT_TYPE_FINANCING = 'finanziamento';
    const CONTRACT_TYPE_LEASING = 'leasing';

// stati riassicurazione
    public static $statusesNsiaMap = [
        '00100' => [
            'status' => self::STATUS_NSIA_00100, // 'In istruttoria'
            'note' => false
        ],
        '00101' => [
            'status' => self::STATUS_NSIA_00101, // 'Esito positivo, in attesa di delibera CTR',
            'note' => false
        ],
        '00102' => [
            'status' => self::STATUS_NSIA_00102, // 'Esito Negativo, in attesa di delibera CTR',
            'note' => true
        ],
        '00103' => [
            'status' => self::STATUS_NSIA_00103, // 'Deliberata Negativa',
            'note' => true
        ],
        '00104' => [
            'status' => self::STATUS_NSIA_00104, // 'Deliberata Positiva',
            'note' => false
        ],
'00111' => [
    'status' => self::STATUS_NSIA_00111, // 'Deliberata Positiva, in attesa di perfezionamento',
    'note' => false
],
'00110' => [
    'status' => self::STATUS_NSIA_00110, // 'Perfezionata',
    'note' => false
],
        '00105' => [
            'status' => self::STATUS_NSIA_00105, // 'Garanzia Scaduta',
            'note' => false
        ],
        '00106' => [
            'status' => self::STATUS_NSIA_00106, // 'Annullata',
            'note' => true
        ],
        '00107' => [
            'status' => self::STATUS_NSIA_00107, // 'Garanzia inescutibile',
            'note' => true
        ],
        '00108' => [
            'status' => self::STATUS_NSIA_00108, // 'Revocata per estinzione finanziamento',
            'note' => false
        ],
        '00109' => [
            'status' => self::STATUS_NSIA_00109, // 'Rinunciata',
            'note' => true
        ],
        '00200' => [
            'status' => self::STATUS_NSIA_00200, // 'Richiesta Escussione in lavorazione',
            'note' => false
        ],
        '00201' => [
            'status' => self::STATUS_NSIA_00201, // 'Escussione Rifiutata',
            'note' => true
        ],
        '00202' => [
            'status' => self::STATUS_NSIA_00202, // 'Escussione Approvata',
            'note' => false
        ],
        '00205' => [
            'status' => self::STATUS_NSIA_00205, // 'Escussa',
            'note' => false
        ],
        '00206' => [
            'status' => self::STATUS_NSIA_00206, // 'Escussa ma parzialmente restituita',
            'note' => false
        ],
        '00207' => [
            'status' => self::STATUS_NSIA_00207, // 'Escussa ma completamente restituita',
            'note' => false
        ],
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * (strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=ApplicationImport::class, inversedBy="applications")
     * @ORM\JoinColumn(nullable=false)
     */
    private $applicationImport;

    /**
     * @ORM\ManyToOne(targetEntity=RegistryFileAudit::class, inversedBy="applications")
     * @ORM\JoinColumn(nullable=true)
     */
    private $registryFileAudit;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $practiceId;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $flagEnergia;

    /**
     * @ORM\Column(type="float")
     */
    private $aeGAssuranceAmount;

    /**
     * @ORM\Column(type="date")
     */
    private $aeGResolutionDate;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $aeAcCommissionsRebateRequest;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $aeAcInterestsContributionRequest;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $aeAcLostFundContributionRequest;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $aeAcContributionApplicationAmount;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $aeAcApplicationMembers;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $aeIbBusinessName;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $aeIbFiscalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeIbSize;

    /**
     * @ORM\Column(type="date")
     */
    private $aeIbConstitutionDate;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $aeIbChamberOfCommerceCode;

    /**
     * @ORM\Column(type="date")
     */
    private $aeIbChamberOfCommerceRegistrationDate;

    /**
     * @ORM\Column(type="string", length=9)
     */
    private $aeIbAIACode;

    /**
     * @ORM\Column(type="date")
     */
    private $aeIbAIARegistrationDate;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $aeIbAtecoCode;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $aeIbAtecoStartDate;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $aeIbIban;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $aeIbLegalForm;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $aePecAddress;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $aeOfficeAddress;

    /**
     * @ORM\Column(type="string", length=8)
     */
    private $aeOfficePostcode;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $aeOfficeCity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeWorkplaceAddress;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    private $aeWorkplacePostcode;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $aeWorkplaceCity;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $aeOwnerLastname;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $aeOwnerFirstname;

    /**
     * @ORM\Column(type="date")
     */
    private $aeOwnerBirthDate;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $aeOwnerGender;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $aeOwnerFiscalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeOwnerBirthCity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeOwnerBirthCountry;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $aeOwnerJoinDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeFirstMemberLastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeFirstMemberFirstname;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $aeFirstMemberBirthDate;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $aeFirstMemberGender;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $aeFirstMemberFiscalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeFirstMemberBirthCity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeFirstMemberBirthCountry;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $aeFirstMemberJoinDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeSecondMemberLastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeSecondMemberFirstname;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $aeSecondMemberBirthDate;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $aeSecondMemberGender;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $aeSecondMemberFiscalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeSecondMemberBirthCity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeSecondMemberBirthCountry;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $aeSecondMemberJoinDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeThirdMemberLastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeThirdMemberFirstname;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $aeThirdMemberBirthDate;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $aeThirdMemberGender;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $aeThirdMemberFiscalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeThirdMemberBirthCity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeThirdMemberBirthCountry;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $aeThirdMemberJoinDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeFourthMemberLastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeFourthMemberFirstname;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $aeFourthMemberBirthDate;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $aeFourthMemberGender;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $aeFourthMemberFiscalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeFourthMemberBirthCity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeFourthMemberBirthCountry;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $aeFourthMemberJoinDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fDbfBank;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fDbfBusinessName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fDbfABI;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fFFinancialDestination;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $fDfLoanProvidedAtImport;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $fDfAmount;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $fDfContractSignatureDate;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $fDfResolutionDate;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $fDfIssueDate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fDfDuration;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $fDfPeriodicity;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $fDfFirstDepreciationDeadline;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $fDfPreDepreciationExists;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $fDfInstallmentAmount;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $fTRateType;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $fTRate;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $fTTaeg;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lSfBankLeasing;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lSfBusinessName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lSfLeasingDestination;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $lDclAmount;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $lDclContractSignatureDate;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $lDclResolutionDate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $lDclDuration;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $lDclPeriodicity;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $lDclFirstDeadline;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $lDclFeeAmount;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $lDclFeePercentage;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $lDclRansomPercentage;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $lDclRate;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $protocolNumber;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $protocolDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(name="created_by", onDelete="SET NULL")
     * @Gedmo\Blameable(on="create")
     */
    private $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(name="updated_by", onDelete="SET NULL")
     * @Gedmo\Blameable(on="update")
     */
    private $updatedBy;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\OneToMany(
     *     targetEntity=ApplicationStatusLog::class,
     *     mappedBy="application",
     *     cascade={"all"},
     *     orphanRemoval=true
     * )
     * @ORM\OrderBy({"createdAt" = "DESC"})
     */
    private $applicationStatusLogs;

    /**
     * @ORM\OneToMany(targetEntity=ApplicationMessage::class, mappedBy="application", orphanRemoval=true)
     */
    private $applicationMessages;

    /**
     * @ORM\Column(type="string", name="`status`", length=64)
     */
    private $status = self::STATUS_CREATED;

    /**
     * @ORM\ManyToOne(targetEntity=ApplicationGroup::class, inversedBy="applications")
     */
    private $applicationGroup;

    /**
     * @ORM\Column(type="string", length=64, options={"default": Application::INQUEST_STATUS_CLOSED})
     */
    private $inquestStatus = self::INQUEST_STATUS_CLOSED;

    /**
     * @ORM\OneToMany(targetEntity=AdditionalContribution::class, mappedBy="application", orphanRemoval=true, cascade={"all"})
     */
    private $additionalContributions;

    /**
     * @ORM\ManyToOne(targetEntity=Confidi::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $confidi;

    /**
     * @ORM\OneToMany(targetEntity=ApplicationAttachment::class, mappedBy="application", orphanRemoval=true, cascade={"persist"})
     */
    private $applicationAttachments;



    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $nsiaNumeroPosizione;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataProtocollo;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $nsiaNota;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataDelibera;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nsiaCodiceCor;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataRilascioCor;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaLDclImportoRiscatto;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nsiaDurataGaranzia;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaImportoRiassicurazione;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaEslRiassicurazione;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataInizioGaranzia;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataFineGaranzia;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataLiquidazioneConfidi;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaImportoPerditaConfidi;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataRichiestaRimborso;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataProtocolloPerdita;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataDeliberaPerdita;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaImportoRimborsoPrenotato;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaImportoRimborsato;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataLiquidazione;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaImportoRestituitoConfidi;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataRestituzioneConfidi;

    /**
     * @ORM\OneToOne(targetEntity=FinancingProvisioningCertification::class, mappedBy="application", cascade={"persist", "remove"})
     */
    private $financingProvisioningCertification;



    public function __construct()
    {
        $this->applicationStatusLogs = new ArrayCollection();
        $this->applicationMessages = new ArrayCollection();
        $this->additionalContributions = new ArrayCollection();
        $this->applicationAttachments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplicationImport(): ?ApplicationImport
    {
        return $this->applicationImport;
    }

    public function setApplicationImport(?ApplicationImport $applicationImport): self
    {
        $this->applicationImport = $applicationImport;

        return $this;
    }

    public function getRegistryFileAudit(): ?RegistryFileAudit
    {
        return $this->registryFileAudit;
    }

    public function setRegistryFileAudit(?RegistryFileAudit $registryFileAudit): self
    {
        $this->registryFileAudit = $registryFileAudit;

        return $this;
    }

    public function getPracticeId(): ?string
    {
        return $this->practiceId;
    }

    public function setPracticeId(string $practiceId): self
    {
        $this->practiceId = $practiceId;

        return $this;
    }

    public function getFlagEnergia(): ?string
    {
        return $this->flagEnergia;
    }

    public function setFlagEnergia(string $flagEnergia): self
    {
        $this->flagEnergia = $flagEnergia;

        return $this;
    }

    public function getAeGAssuranceAmount(): ?float
    {
        return $this->aeGAssuranceAmount;
    }

    public function setAeGAssuranceAmount(float $aeGAssuranceAmount): self
    {
        $this->aeGAssuranceAmount = $aeGAssuranceAmount;

        return $this;
    }

    public function getAeGResolutionDate(): ?\DateTimeInterface
    {
        return $this->aeGResolutionDate;
    }

    public function setAeGResolutionDate(\DateTimeInterface $aeGResolutionDate): self
    {
        $this->aeGResolutionDate = $aeGResolutionDate;

        return $this;
    }

    public function getAeAcCommissionsRebateRequest(): ?string
    {
        return $this->aeAcCommissionsRebateRequest;
    }

    public function setAeAcCommissionsRebateRequest(string $aeAcCommissionsRebateRequest): self
    {
        $this->aeAcCommissionsRebateRequest = $aeAcCommissionsRebateRequest;

        return $this;
    }

    public function getAeAcInterestsContributionRequest(): ?string
    {
        return $this->aeAcInterestsContributionRequest;
    }

    public function setAeAcInterestsContributionRequest(string $aeAcInterestsContributionRequest): self
    {
        $this->aeAcInterestsContributionRequest = $aeAcInterestsContributionRequest;

        return $this;
    }

    public function getAeAcLostFundContributionRequest(): ?string
    {
        return $this->aeAcLostFundContributionRequest;
    }

    public function setAeAcLostFundContributionRequest(string $aeAcLostFundContributionRequest): self
    {
        $this->aeAcLostFundContributionRequest = $aeAcLostFundContributionRequest;

        return $this;
    }

    public function getAeAcContributionApplicationAmount(): ?float
    {
        return $this->aeAcContributionApplicationAmount;
    }

    public function setAeAcContributionApplicationAmount(?float $aeAcContributionApplicationAmount): self
    {
        $this->aeAcContributionApplicationAmount = $aeAcContributionApplicationAmount;

        return $this;
    }

    public function getAeAcApplicationMembers(): ?int
    {
        return $this->aeAcApplicationMembers;
    }

    public function setAeAcApplicationMembers(?int $aeAcApplicationMembers): self
    {
        $this->aeAcApplicationMembers = $aeAcApplicationMembers;

        return $this;
    }

    public function getAeIbBusinessName(): ?string
    {
        return $this->aeIbBusinessName;
    }

    public function setAeIbBusinessName(string $aeIbBusinessName): self
    {
        $this->aeIbBusinessName = $aeIbBusinessName;

        return $this;
    }

    public function getAeIbFiscalCode(): ?string
    {
        return $this->aeIbFiscalCode;
    }

    public function setAeIbFiscalCode(string $aeIbFiscalCode): self
    {
        $this->aeIbFiscalCode = $aeIbFiscalCode;

        return $this;
    }

    public function getAeIbSize(): ?string
    {
        return $this->aeIbSize;
    }

    public function setAeIbSize(?string $aeIbSize): self
    {
        $this->aeIbSize = $aeIbSize;

        return $this;
    }

    public function getAeIbConstitutionDate(): ?\DateTimeInterface
    {
        return $this->aeIbConstitutionDate;
    }

    public function setAeIbConstitutionDate(\DateTimeInterface $aeIbConstitutionDate): self
    {
        $this->aeIbConstitutionDate = $aeIbConstitutionDate;

        return $this;
    }

    public function getAeIbChamberOfCommerceCode(): ?string
    {
        return $this->aeIbChamberOfCommerceCode;
    }

    public function setAeIbChamberOfCommerceCode(string $aeIbChamberOfCommerceCode): self
    {
        $this->aeIbChamberOfCommerceCode = $aeIbChamberOfCommerceCode;

        return $this;
    }

    public function getAeIbChamberOfCommerceRegistrationDate(): ?\DateTimeInterface
    {
        return $this->aeIbChamberOfCommerceRegistrationDate;
    }

    public function setAeIbChamberOfCommerceRegistrationDate(\DateTimeInterface $aeIbChamberOfCommerceRegistrationDate): self
    {
        $this->aeIbChamberOfCommerceRegistrationDate = $aeIbChamberOfCommerceRegistrationDate;

        return $this;
    }

    public function getAeIbAIACode(): ?string
    {
        return $this->aeIbAIACode;
    }

    public function setAeIbAIACode(string $aeIbAIACode): self
    {
        $this->aeIbAIACode = $aeIbAIACode;

        return $this;
    }

    public function getAeIbAIARegistrationDate(): ?\DateTimeInterface
    {
        return $this->aeIbAIARegistrationDate;
    }

    public function setAeIbAIARegistrationDate(\DateTimeInterface $aeIbAIARegistrationDate): self
    {
        $this->aeIbAIARegistrationDate = $aeIbAIARegistrationDate;

        return $this;
    }

    public function getAeIbAtecoCode(): ?string
    {
        return $this->aeIbAtecoCode;
    }

    public function setAeIbAtecoCode(string $aeIbAtecoCode): self
    {
        $this->aeIbAtecoCode = $aeIbAtecoCode;

        return $this;
    }

    public function getAeIbAtecoStartDate(): ?\DateTimeInterface
    {
        return $this->aeIbAtecoStartDate;
    }

    public function setAeIbAtecoStartDate(?\DateTimeInterface $aeIbAtecoStartDate): self
    {
        $this->aeIbAtecoStartDate = $aeIbAtecoStartDate;

        return $this;
    }

    public function getAeIbIban(): ?string
    {
        $this->aeIbIban = preg_replace('/\s+/', '', $this->aeIbIban) ?? null;
        return $this->aeIbIban;
    }

    public function setAeIbIban(?string $aeIbIban): self
    {
        $aeIbIban = preg_replace('/\s+/', '', $aeIbIban) ?? null;
        $this->aeIbIban = $aeIbIban;

        return $this;
    }

    public function getAeIbLegalForm(): ?string
    {
        return $this->aeIbLegalForm;
    }

    public function setAeIbLegalForm(string $aeIbLegalForm): self
    {
        $this->aeIbLegalForm = $aeIbLegalForm;

        return $this;
    }

    public function getAePecAddress(): ?string
    {
        return $this->aePecAddress;
    }

    public function setAePecAddress(string $aePecAddress): self
    {
        $this->aePecAddress = $aePecAddress;

        return $this;
    }

    public function getAeOfficeAddress(): ?string
    {
        return $this->aeOfficeAddress;
    }

    public function setAeOfficeAddress(string $aeOfficeAddress): self
    {
        $this->aeOfficeAddress = $aeOfficeAddress;

        return $this;
    }

    public function getAeOfficePostcode(): ?string
    {
        return $this->aeOfficePostcode;
    }

    public function setAeOfficePostcode(string $aeOfficePostcode): self
    {
        $this->aeOfficePostcode = $aeOfficePostcode;

        return $this;
    }

    public function getAeOfficeCity(): ?string
    {
        return $this->aeOfficeCity;
    }

    public function setAeOfficeCity(string $aeOfficeCity): self
    {
        $this->aeOfficeCity = $aeOfficeCity;

        return $this;
    }

    public function getAeWorkplaceAddress(): ?string
    {
        return $this->aeWorkplaceAddress;
    }

    public function setAeWorkplaceAddress(?string $aeWorkplaceAddress): self
    {
        $this->aeWorkplaceAddress = $aeWorkplaceAddress;

        return $this;
    }

    public function getAeWorkplacePostcode(): ?string
    {
        return $this->aeWorkplacePostcode;
    }

    public function setAeWorkplacePostcode(?string $aeWorkplacePostcode): self
    {
        $this->aeWorkplacePostcode = $aeWorkplacePostcode;

        return $this;
    }

    public function getAeWorkplaceCity(): ?string
    {
        return $this->aeWorkplaceCity;
    }

    public function setAeWorkplaceCity(?string $aeWorkplaceCity): self
    {
        $this->aeWorkplaceCity = $aeWorkplaceCity;

        return $this;
    }

    public function getAeOwnerLastname(): ?string
    {
        return $this->aeOwnerLastname;
    }

    public function setAeOwnerLastname(string $aeOwnerLastname): self
    {
        $this->aeOwnerLastname = $aeOwnerLastname;

        return $this;
    }

    public function getAeOwnerFirstname(): ?string
    {
        return $this->aeOwnerFirstname;
    }

    public function setAeOwnerFirstname(string $aeOwnerFirstname): self
    {
        $this->aeOwnerFirstname = $aeOwnerFirstname;

        return $this;
    }

    public function getAeOwnerBirthDate(): ?\DateTimeInterface
    {
        return $this->aeOwnerBirthDate;
    }

    public function setAeOwnerBirthDate(\DateTimeInterface $aeOwnerBirthDate): self
    {
        $this->aeOwnerBirthDate = $aeOwnerBirthDate;

        return $this;
    }

    public function getAeOwnerGender(): ?string
    {
        return $this->aeOwnerGender;
    }

    public function setAeOwnerGender(string $aeOwnerGender): self
    {
        $this->aeOwnerGender = $aeOwnerGender;

        return $this;
    }

    public function getAeOwnerFiscalCode(): ?string
    {
        return $this->aeOwnerFiscalCode;
    }

    public function setAeOwnerFiscalCode(string $aeOwnerFiscalCode): self
    {
        $this->aeOwnerFiscalCode = $aeOwnerFiscalCode;

        return $this;
    }

    public function getAeOwnerBirthCity(): ?string
    {
        return $this->aeOwnerBirthCity;
    }

    public function setAeOwnerBirthCity(?string $aeOwnerBirthCity): self
    {
        $this->aeOwnerBirthCity = $aeOwnerBirthCity;

        return $this;
    }

    public function getAeOwnerBirthCountry(): ?string
    {
        return $this->aeOwnerBirthCountry;
    }

    public function setAeOwnerBirthCountry(?string $aeOwnerBirthCountry): self
    {
        $this->aeOwnerBirthCountry = $aeOwnerBirthCountry;

        return $this;
    }

    public function getAeOwnerJoinDate(): ?\DateTimeInterface
    {
        return $this->aeOwnerJoinDate;
    }

    public function setAeOwnerJoinDate(?\DateTimeInterface $aeOwnerJoinDate): self
    {
        $this->aeOwnerJoinDate = $aeOwnerJoinDate;

        return $this;
    }

    public function getAeFirstMemberLastname(): ?string
    {
        return $this->aeFirstMemberLastname;
    }

    public function setAeFirstMemberLastname(?string $aeFirstMemberLastname): self
    {
        $this->aeFirstMemberLastname = $aeFirstMemberLastname;

        return $this;
    }

    public function getAeFirstMemberFirstname(): ?string
    {
        return $this->aeFirstMemberFirstname;
    }

    public function setAeFirstMemberFirstname(?string $aeFirstMemberFirstname): self
    {
        $this->aeFirstMemberFirstname = $aeFirstMemberFirstname;

        return $this;
    }

    public function getAeFirstMemberBirthDate(): ?\DateTimeInterface
    {
        return $this->aeFirstMemberBirthDate;
    }

    public function setAeFirstMemberBirthDate(?\DateTimeInterface $aeFirstMemberBirthDate): self
    {
        $this->aeFirstMemberBirthDate = $aeFirstMemberBirthDate;

        return $this;
    }

    public function getAeFirstMemberGender(): ?string
    {
        return $this->aeFirstMemberGender;
    }

    public function setAeFirstMemberGender(?string $aeFirstMemberGender): self
    {
        $this->aeFirstMemberGender = $aeFirstMemberGender;

        return $this;
    }

    public function getAeFirstMemberFiscalCode(): ?string
    {
        return $this->aeFirstMemberFiscalCode;
    }

    public function setAeFirstMemberFiscalCode(?string $aeFirstMemberFiscalCode): self
    {
        $this->aeFirstMemberFiscalCode = $aeFirstMemberFiscalCode;

        return $this;
    }

    public function getAeFirstMemberBirthCity(): ?string
    {
        return $this->aeFirstMemberBirthCity;
    }

    public function setAeFirstMemberBirthCity(?string $aeFirstMemberBirthCity): self
    {
        $this->aeFirstMemberBirthCity = $aeFirstMemberBirthCity;

        return $this;
    }

    public function getAeFirstMemberBirthCountry(): ?string
    {
        return $this->aeFirstMemberBirthCountry;
    }

    public function setAeFirstMemberBirthCountry(?string $aeFirstMemberBirthCountry): self
    {
        $this->aeFirstMemberBirthCountry = $aeFirstMemberBirthCountry;

        return $this;
    }

    public function getAeFirstMemberJoinDate(): ?\DateTimeInterface
    {
        return $this->aeFirstMemberJoinDate;
    }

    public function setAeFirstMemberJoinDate(?\DateTimeInterface $aeFirstMemberJoinDate): self
    {
        $this->aeFirstMemberJoinDate = $aeFirstMemberJoinDate;

        return $this;
    }

    public function getAeSecondMemberLastname(): ?string
    {
        return $this->aeSecondMemberLastname;
    }

    public function setAeSecondMemberLastname(?string $aeSecondMemberLastname): self
    {
        $this->aeSecondMemberLastname = $aeSecondMemberLastname;

        return $this;
    }

    public function getAeSecondMemberFirstname(): ?string
    {
        return $this->aeSecondMemberFirstname;
    }

    public function setAeSecondMemberFirstname(?string $aeSecondMemberFirstname): self
    {
        $this->aeSecondMemberFirstname = $aeSecondMemberFirstname;

        return $this;
    }

    public function getAeSecondMemberBirthDate(): ?\DateTimeInterface
    {
        return $this->aeSecondMemberBirthDate;
    }

    public function setAeSecondMemberBirthDate(?\DateTimeInterface $aeSecondMemberBirthDate): self
    {
        $this->aeSecondMemberBirthDate = $aeSecondMemberBirthDate;

        return $this;
    }

    public function getAeSecondMemberGender(): ?string
    {
        return $this->aeSecondMemberGender;
    }

    public function setAeSecondMemberGender(?string $aeSecondMemberGender): self
    {
        $this->aeSecondMemberGender = $aeSecondMemberGender;

        return $this;
    }

    public function getAeSecondMemberFiscalCode(): ?string
    {
        return $this->aeSecondMemberFiscalCode;
    }

    public function setAeSecondMemberFiscalCode(?string $aeSecondMemberFiscalCode): self
    {
        $this->aeSecondMemberFiscalCode = $aeSecondMemberFiscalCode;

        return $this;
    }

    public function getAeSecondMemberBirthCity(): ?string
    {
        return $this->aeSecondMemberBirthCity;
    }

    public function setAeSecondMemberBirthCity(?string $aeSecondMemberBirthCity): self
    {
        $this->aeSecondMemberBirthCity = $aeSecondMemberBirthCity;

        return $this;
    }

    public function getAeSecondMemberBirthCountry(): ?string
    {
        return $this->aeSecondMemberBirthCountry;
    }

    public function setAeSecondMemberBirthCountry(?string $aeSecondMemberBirthCountry): self
    {
        $this->aeSecondMemberBirthCountry = $aeSecondMemberBirthCountry;

        return $this;
    }

    public function getAeSecondMemberJoinDate(): ?\DateTimeInterface
    {
        return $this->aeSecondMemberJoinDate;
    }

    public function setAeSecondMemberJoinDate(?\DateTimeInterface $aeSecondMemberJoinDate): self
    {
        $this->aeSecondMemberJoinDate = $aeSecondMemberJoinDate;

        return $this;
    }

    public function getAeThirdMemberLastname(): ?string
    {
        return $this->aeThirdMemberLastname;
    }

    public function setAeThirdMemberLastname(?string $aeThirdMemberLastname): self
    {
        $this->aeThirdMemberLastname = $aeThirdMemberLastname;

        return $this;
    }

    public function getAeThirdMemberFirstname(): ?string
    {
        return $this->aeThirdMemberFirstname;
    }

    public function setAeThirdMemberFirstname(?string $aeThirdMemberFirstname): self
    {
        $this->aeThirdMemberFirstname = $aeThirdMemberFirstname;

        return $this;
    }

    public function getAeThirdMemberBirthDate(): ?\DateTimeInterface
    {
        return $this->aeThirdMemberBirthDate;
    }

    public function setAeThirdMemberBirthDate(?\DateTimeInterface $aeThirdMemberBirthDate): self
    {
        $this->aeThirdMemberBirthDate = $aeThirdMemberBirthDate;

        return $this;
    }

    public function getAeThirdMemberGender(): ?string
    {
        return $this->aeThirdMemberGender;
    }

    public function setAeThirdMemberGender(?string $aeThirdMemberGender): self
    {
        $this->aeThirdMemberGender = $aeThirdMemberGender;

        return $this;
    }

    public function getAeThirdMemberFiscalCode(): ?string
    {
        return $this->aeThirdMemberFiscalCode;
    }

    public function setAeThirdMemberFiscalCode(?string $aeThirdMemberFiscalCode): self
    {
        $this->aeThirdMemberFiscalCode = $aeThirdMemberFiscalCode;

        return $this;
    }

    public function getAeThirdMemberBirthCity(): ?string
    {
        return $this->aeThirdMemberBirthCity;
    }

    public function setAeThirdMemberBirthCity(?string $aeThirdMemberBirthCity): self
    {
        $this->aeThirdMemberBirthCity = $aeThirdMemberBirthCity;

        return $this;
    }

    public function getAeThirdMemberBirthCountry(): ?string
    {
        return $this->aeThirdMemberBirthCountry;
    }

    public function setAeThirdMemberBirthCountry(?string $aeThirdMemberBirthCountry): self
    {
        $this->aeThirdMemberBirthCountry = $aeThirdMemberBirthCountry;

        return $this;
    }

    public function getAeThirdMemberJoinDate(): ?\DateTimeInterface
    {
        return $this->aeThirdMemberJoinDate;
    }

    public function setAeThirdMemberJoinDate(?\DateTimeInterface $aeThirdMemberJoinDate): self
    {
        $this->aeThirdMemberJoinDate = $aeThirdMemberJoinDate;

        return $this;
    }

    public function getAeFourthMemberLastname(): ?string
    {
        return $this->aeFourthMemberLastname;
    }

    public function setAeFourthMemberLastname(?string $aeFourthMemberLastname): self
    {
        $this->aeFourthMemberLastname = $aeFourthMemberLastname;

        return $this;
    }

    public function getAeFourthMemberFirstname(): ?string
    {
        return $this->aeFourthMemberFirstname;
    }

    public function setAeFourthMemberFirstname(?string $aeFourthMemberFirstname): self
    {
        $this->aeFourthMemberFirstname = $aeFourthMemberFirstname;

        return $this;
    }

    public function getAeFourthMemberBirthDate(): ?\DateTimeInterface
    {
        return $this->aeFourthMemberBirthDate;
    }

    public function setAeFourthMemberBirthDate(?\DateTimeInterface $aeFourthMemberBirthDate): self
    {
        $this->aeFourthMemberBirthDate = $aeFourthMemberBirthDate;

        return $this;
    }

    public function getAeFourthMemberGender(): ?string
    {
        return $this->aeFourthMemberGender;
    }

    public function setAeFourthMemberGender(?string $aeFourthMemberGender): self
    {
        $this->aeFourthMemberGender = $aeFourthMemberGender;

        return $this;
    }

    public function getAeFourthMemberFiscalCode(): ?string
    {
        return $this->aeFourthMemberFiscalCode;
    }

    public function setAeFourthMemberFiscalCode(?string $aeFourthMemberFiscalCode): self
    {
        $this->aeFourthMemberFiscalCode = $aeFourthMemberFiscalCode;

        return $this;
    }

    public function getAeFourthMemberBirthCity(): ?string
    {
        return $this->aeFourthMemberBirthCity;
    }

    public function setAeFourthMemberBirthCity(?string $aeFourthMemberBirthCity): self
    {
        $this->aeFourthMemberBirthCity = $aeFourthMemberBirthCity;

        return $this;
    }

    public function getAeFourthMemberBirthCountry(): ?string
    {
        return $this->aeFourthMemberBirthCountry;
    }

    public function setAeFourthMemberBirthCountry(?string $aeFourthMemberBirthCountry): self
    {
        $this->aeFourthMemberBirthCountry = $aeFourthMemberBirthCountry;

        return $this;
    }

    public function getAeFourthMemberJoinDate(): ?\DateTimeInterface
    {
        return $this->aeFourthMemberJoinDate;
    }

    public function setAeFourthMemberJoinDate(?\DateTimeInterface $aeFourthMemberJoinDate): self
    {
        $this->aeFourthMemberJoinDate = $aeFourthMemberJoinDate;

        return $this;
    }

    public function getFDbfBank(): ?string
    {
        return $this->fDbfBank;
    }

    public function setFDbfBank(?string $fDbfBank): self
    {
        $this->fDbfBank = $fDbfBank;

        return $this;
    }

    public function getFDbfBusinessName(): ?string
    {
        return $this->fDbfBusinessName;
    }

    public function setFDbfBusinessName(?string $fDbfBusinessName): self
    {
        $this->fDbfBusinessName = $fDbfBusinessName;

        return $this;
    }

    public function getFDbfABI(): ?string
    {
        return $this->fDbfABI;
    }

    public function setFDbfABI(?string $fDbfABI): self
    {
        $this->fDbfABI = $fDbfABI;

        return $this;
    }

    public function getFFinancialDestination(): ?string
    {
        return $this->fFFinancialDestination;
    }

    public function setFFinancialDestination(?string $fFFinancialDestination): self
    {
        $this->fFFinancialDestination = $fFFinancialDestination;

        return $this;
    }

    public function getFDfLoanProvidedAtImport(): ?string
    {
        return $this->fDfLoanProvidedAtImport;
    }

    public function setFDfLoanProvidedAtImport(?string $fDfLoanProvidedAtImport): self
    {
        $this->fDfLoanProvidedAtImport = $fDfLoanProvidedAtImport;

        return $this;
    }

    public function getFDfAmount(): ?float
    {
        return $this->fDfAmount;
    }

    public function setFDfAmount(?float $fDfAmount): self
    {
        $this->fDfAmount = $fDfAmount;

        return $this;
    }

    public function getFDfContractSignatureDate(): ?\DateTimeInterface
    {
        return $this->fDfContractSignatureDate;
    }

    public function setFDfContractSignatureDate(?\DateTimeInterface $fDfContractSignatureDate): self
    {
        $this->fDfContractSignatureDate = $fDfContractSignatureDate;

        return $this;
    }

    public function getFDfResolutionDate(): ?\DateTimeInterface
    {
        return $this->fDfResolutionDate;
    }

    public function setFDfResolutionDate(?\DateTimeInterface $fDfResolutionDate): self
    {
        $this->fDfResolutionDate = $fDfResolutionDate;

        return $this;
    }

    public function getFDfIssueDate(): ?\DateTimeInterface
    {
        return $this->fDfIssueDate;
    }

    public function setFDfIssueDate(?\DateTimeInterface $fDfIssueDate): self
    {
        $this->fDfIssueDate = $fDfIssueDate;

        return $this;
    }

    public function getFDfDuration(): ?int
    {
        return $this->fDfDuration;
    }

    public function setFDfDuration(?int $fDfDuration): self
    {
        $this->fDfDuration = $fDfDuration;

        return $this;
    }

    public function getFDfPeriodicity(): ?string
    {
        return $this->fDfPeriodicity;
    }

    public function setFDfPeriodicity(?string $fDfPeriodicity): self
    {
        $this->fDfPeriodicity = $fDfPeriodicity;

        return $this;
    }

    public function getFDfFirstDepreciationDeadline(): ?\DateTimeInterface
    {
        return $this->fDfFirstDepreciationDeadline;
    }

    public function setFDfFirstDepreciationDeadline(?\DateTimeInterface $fDfFirstDepreciationDeadline): self
    {
        $this->fDfFirstDepreciationDeadline = $fDfFirstDepreciationDeadline;

        return $this;
    }

    public function getFDfPreDepreciationExists(): ?string
    {
        return $this->fDfPreDepreciationExists;
    }

    public function setFDfPreDepreciationExists(?string $fDfPreDepreciationExists): self
    {
        $this->fDfPreDepreciationExists = $fDfPreDepreciationExists;

        return $this;
    }

    public function getFDfInstallmentAmount(): ?float
    {
        return $this->fDfInstallmentAmount;
    }

    public function setFDfInstallmentAmount(?float $fDfInstallmentAmount): self
    {
        $this->fDfInstallmentAmount = $fDfInstallmentAmount;

        return $this;
    }

    public function getFTRateType(): ?string
    {
        return $this->fTRateType;
    }

    public function setFTRateType(?string $fTRateType): self
    {
        $this->fTRateType = $fTRateType;

        return $this;
    }

    public function getFTRate(): ?float
    {
        return $this->fTRate;
    }

    public function setFTRate(?float $fTRate): self
    {
        $this->fTRate = $fTRate;

        return $this;
    }

    public function getFTTaeg(): ?float
    {
        return $this->fTTaeg;
    }

    public function setFTTaeg(?float $fTTaeg): self
    {
        $this->fTTaeg = $fTTaeg;

        return $this;
    }

    public function getLSfBankLeasing(): ?string
    {
        return $this->lSfBankLeasing;
    }

    public function setLSfBankLeasing(string $lSfBankLeasing): self
    {
        $this->lSfBankLeasing = $lSfBankLeasing;

        return $this;
    }

    public function getLSfBusinessName(): ?string
    {
        return $this->lSfBusinessName;
    }

    public function setLSfBusinessName(?string $lSfBusinessName): self
    {
        $this->lSfBusinessName = $lSfBusinessName;

        return $this;
    }

    public function getLSfLeasingDestination(): ?string
    {
        return $this->lSfLeasingDestination;
    }

    public function setLSfLeasingDestination(string $lSfLeasingDestination): self
    {
        $this->lSfLeasingDestination = $lSfLeasingDestination;

        return $this;
    }

    public function getLDclAmount(): ?float
    {
        return $this->lDclAmount;
    }

    public function setLDclAmount(?float $lDclAmount): self
    {
        $this->lDclAmount = $lDclAmount;

        return $this;
    }

    public function getLDclContractSignatureDate(): ?\DateTimeInterface
    {
        return $this->lDclContractSignatureDate;
    }

    public function setLDclContractSignatureDate(?\DateTimeInterface $lDclContractSignatureDate): self
    {
        $this->lDclContractSignatureDate = $lDclContractSignatureDate;

        return $this;
    }

    public function getLDclResolutionDate(): ?\DateTimeInterface
    {
        return $this->lDclResolutionDate;
    }

    public function setLDclResolutionDate(?\DateTimeInterface $lDclResolutionDate): self
    {
        $this->lDclResolutionDate = $lDclResolutionDate;

        return $this;
    }

    public function getLDclDuration(): ?int
    {
        return $this->lDclDuration;
    }

    public function setLDclDuration(?int $lDclDuration): self
    {
        $this->lDclDuration = $lDclDuration;

        return $this;
    }

    public function getLDclPeriodicity(): ?string
    {
        return $this->lDclPeriodicity;
    }

    public function setLDclPeriodicity(?string $lDclPeriodicity): self
    {
        $this->lDclPeriodicity = $lDclPeriodicity;

        return $this;
    }

    public function getLDclFirstDeadline(): ?\DateTimeInterface
    {
        return $this->lDclFirstDeadline;
    }

    public function setLDclFirstDeadline(?\DateTimeInterface $lDclFirstDeadline): self
    {
        $this->lDclFirstDeadline = $lDclFirstDeadline;

        return $this;
    }

    public function getLDclFeeAmount(): ?float
    {
        return $this->lDclFeeAmount;
    }

    public function setLDclFeeAmount(?float $lDclFeeAmount): self
    {
        $this->lDclFeeAmount = $lDclFeeAmount;

        return $this;
    }

    public function getLDclFeePercentage(): ?float
    {
        return $this->lDclFeePercentage;
    }

    public function setLDclFeePercentage(?float $lDclFeePercentage): self
    {
        $this->lDclFeePercentage = $lDclFeePercentage;

        return $this;
    }

    public function getLDclRansomPercentage(): ?float
    {
        return $this->lDclRansomPercentage;
    }

    public function setLDclRansomPercentage(?float $lDclRansomPercentage): self
    {
        $this->lDclRansomPercentage = $lDclRansomPercentage;

        return $this;
    }

    public function getLDclRate(): ?float
    {
        return $this->lDclRate;
    }

    public function setLDclRate(?float $lDclRate): self
    {
        $this->lDclRate = $lDclRate;

        return $this;
    }

    public function getProtocolNumber(): ?string
    {
        return $this->protocolNumber;
    }

    public function setProtocolNumber(string $protocolNumber): self
    {
        $this->protocolNumber = $protocolNumber;

        return $this;
    }

    public function getProtocolDate(): ?\DateTimeInterface
    {
        return $this->protocolDate;
    }

    public function setProtocolDate(?\DateTimeInterface $protocolDate): self
    {
        $this->protocolDate = $protocolDate;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return Collection|ApplicationStatusLog[]
     */
    public function getApplicationStatusLogs(): Collection
    {
        return $this->applicationStatusLogs;
    }

    public function addApplicationStatusLog(ApplicationStatusLog $applicationStatusLog): self
    {
        if (!$this->applicationStatusLogs->contains($applicationStatusLog)) {
            $this->applicationStatusLogs[] = $applicationStatusLog;
            $applicationStatusLog->setApplication($this);
        }

        return $this;
    }

    public function removeApplicationStatusLog(ApplicationStatusLog $applicationStatusLog): self
    {
        if ($this->applicationStatusLogs->removeElement($applicationStatusLog)) {
            // set the owning side to null (unless already changed)
            if ($applicationStatusLog->getApplication() === $this) {
                $applicationStatusLog->setApplication(null);
            }
        }

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getInquestStatus(): string
    {
        return $this->inquestStatus;
    }

    /**
     * @param string $inquestStatus
     */
    public function setInquestStatus(string $inquestStatus): void
    {
        $this->inquestStatus = $inquestStatus;
    }

    public function getCode()
    {
        return '<a href="/">Go to home</a>';
    }

    public function __toString()
    {
        return $this->practiceId . ' - ' . $this->getAeIbBusinessName() . ' (' . $this->createdAt->format('d/m/Y') . ')';
    }

    public function getApplicationGroup(): ?ApplicationGroup
    {
        return $this->applicationGroup;
    }

    public function setApplicationGroup(?ApplicationGroup $applicationGroup): self
    {
        $this->applicationGroup = $applicationGroup;
        return $this;
    }

    /**
     * @return Collection|ApplicationMessage[]
     */
    public function getApplicationMessages(): Collection
    {
        return $this->applicationMessages;
    }

    public function addApplicationMessage(ApplicationMessage $applicationMessage): self
    {
        if (!$this->applicationMessages->contains($applicationMessage)) {
            $this->applicationMessages[] = $applicationMessage;
            $applicationMessage->setApplication($this);
        }

        return $this;
    }

    public function removeApplicationMessage(ApplicationMessage $applicationMessage): self
    {
        if ($this->applicationMessages->removeElement($applicationMessage)) {
            // set the owning side to null (unless already changed)
            if ($applicationMessage->getApplication() === $this) {
                $applicationMessage->setApplication(null);
            }
        }
        return $this;
    }

    public function getFirstMessageDraftOfUser(User $user)
    {
        return $this->getApplicationMessages()->filter(function (ApplicationMessage $applicationMessage) use ($user) {
            return !$applicationMessage->getPublished() &&
                $applicationMessage->getCreatedBy() === $user
                ;
        })->first();
    }

    public function getPublishedMessages()
    {
        return $this->getApplicationMessages()->filter(function (ApplicationMessage $applicationMessage) {
            return $applicationMessage->getPublished();
        });
    }

    /**
     * @return Collection|AdditionalContribution[]
     */
    public function getAdditionalContributions(): Collection
    {
        return $this->additionalContributions;
    }

    public function addAdditionalContribution(AdditionalContribution $additionalContribution): self
    {
        if (!$this->additionalContributions->contains($additionalContribution)) {
            $this->additionalContributions[] = $additionalContribution;
            $additionalContribution->setApplication($this);
        }

        return $this;
    }

    public function removeAdditionalContribution(AdditionalContribution $additionalContribution): self
    {
        if ($this->additionalContributions->removeElement($additionalContribution)) {
            // set the owning side to null (unless already changed)
            if ($additionalContribution->getApplication() === $this) {
                $additionalContribution->setApplication(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|AdditionalContribution[]
     */
    public function getNotImportedAdditionalContributions(): Collection
    {
        return $this->additionalContributions->filter(function (AdditionalContribution $additionalContribution){
            return !$additionalContribution->getInImport();
        });
    }

    /**
     * @return Collection|AdditionalContribution[]
     */
    public function getExistingAdditionalContributions(): Collection
    {
        return $this->additionalContributions->filter(function (AdditionalContribution $additionalContribution){
            return $additionalContribution->getId();
        });
    }

    /**
     * @return Collection|AdditionalContribution[]
     */
    public function getAdditionalContributionsOfType(Collection $collection, $type): Collection
    {
        return $collection->filter(function (AdditionalContribution $additionalContribution) use ($type) {
            return $additionalContribution->getType() === $type;
        });
    }

    public function createAdditionalContributionItems()
    {
        if ($this->aeAcInterestsContributionRequest === 'S') {
            $acType = AdditionalContribution::TYPE_CON;
            if ($this->aeAcLostFundContributionRequest === 'S') {
                $acType = AdditionalContribution::TYPE_CFP;
            }
            $additionalContribution = new AdditionalContribution();
            $additionalContribution
                ->setType($acType)
                ->setInImport(!$this->getId())
                ->setPresentationDate(new \DateTime());
            $this->addAdditionalContribution($additionalContribution);
        }
        if ($this->aeAcCommissionsRebateRequest === 'S') {
            $additionalContribution = new AdditionalContribution();
            $additionalContribution
                ->setType(AdditionalContribution::TYPE_ABB)
                ->setInImport(!$this->getId())
                ->setPresentationDate(new \DateTime());
            $this->addAdditionalContribution($additionalContribution);
        }
    }

    public function createNotImportedAdditionalContributionItems()
    {
        $notImportedAdditionalContributions = $this->getNotImportedAdditionalContributions();
        if (
            $this->aeAcCommissionsRebateRequest === 'N' &&
            $this->getAdditionalContributionsOfType(
                $notImportedAdditionalContributions,
                AdditionalContribution::TYPE_ABB
            )->count() === 0
        ) {
            $additionalContribution = new AdditionalContribution();
            $additionalContribution
                ->setType(AdditionalContribution::TYPE_ABB)
                ->setInImport(false)
                ->setPresentationDate(new \DateTime())
                ->setFormOrderBy(AdditionalContribution::$formOrderByMap[AdditionalContribution::TYPE_ABB])
            ;
            $this->addAdditionalContribution($additionalContribution);
        }

        if (
            $this->aeAcInterestsContributionRequest === 'N' &&
            $this->getAdditionalContributionsOfType(
                $notImportedAdditionalContributions,
                AdditionalContribution::TYPE_CON
            )->count() === 0
        ) {
            $additionalContribution = new AdditionalContribution();
            $additionalContribution
                ->setType(AdditionalContribution::TYPE_CON)
                ->setInImport(false)
                ->setPresentationDate(new \DateTime())
                ->setFormOrderBy(AdditionalContribution::$formOrderByMap[AdditionalContribution::TYPE_CON])
            ;
            $this->addAdditionalContribution($additionalContribution);
        }

        if (
            $this->aeAcLostFundContributionRequest === 'N' &&
            $this->getAdditionalContributionsOfType(
                $notImportedAdditionalContributions,
                AdditionalContribution::TYPE_CFP
            )->count() === 0 &&
            $this->getAdditionalContributionsOfType(
                $this->getExistingAdditionalContributions(),
                AdditionalContribution::TYPE_CON
            )->count() === 0
        ) {
            $additionalContribution = new AdditionalContribution();
            $additionalContribution
                ->setType(AdditionalContribution::TYPE_CFP)
                ->setInImport(false)
                ->setPresentationDate(new \DateTime())
                ->setFormOrderBy(AdditionalContribution::$formOrderByMap[AdditionalContribution::TYPE_CFP])
            ;
            $this->addAdditionalContribution($additionalContribution);
        }
    }

    public function getConfidi(): ?Confidi
    {
        return $this->confidi;
    }

    public function setConfidi(?Confidi $confidi): self
    {
        $this->confidi = $confidi;

        return $this;
    }

    /**
     * @return Collection|ApplicationAttachment[]
     */
    public function getApplicationAttachments(): Collection
    {
        return $this->applicationAttachments;
    }

    public function addApplicationAttachment(ApplicationAttachment $applicationAttachment): self
    {
        if (!$this->applicationAttachments->contains($applicationAttachment)) {
            $this->applicationAttachments[] = $applicationAttachment;
            $applicationAttachment->setApplication($this);
        }

        return $this;
    }

    public function removeApplicationAttachment(ApplicationAttachment $applicationAttachment): self
    {
        if ($this->applicationAttachments->removeElement($applicationAttachment)) {
            // set the owning side to null (unless already changed)
            if ($applicationAttachment->getApplication() === $this) {
                $applicationAttachment->setApplication(null);
            }
        }

        return $this;
    }



    public function getNsiaNumeroPosizione(): ?string
    {
        return $this->nsiaNumeroPosizione;
    }

    public function setNsiaNumeroPosizione(string $nsiaNumeroPosizione): self
    {
        $this->nsiaNumeroPosizione = $nsiaNumeroPosizione;

        return $this;
    }

    public function getNsiaDataProtocollo(): ?\DateTimeInterface
    {
        return $this->nsiaDataProtocollo;
    }

    public function setNsiaDataProtocollo(?\DateTimeInterface $nsiaDataProtocollo): self
    {
        $this->nsiaDataProtocollo = $nsiaDataProtocollo;

        return $this;
    }

    public function getNsiaNota(): ?string
    {
        return $this->nsiaNota;
    }

    public function setNsiaNota(string $nsiaNota): self
    {
        $this->nsiaNota = $nsiaNota;

        return $this;
    }

    public function getNsiaDataDelibera(): ?\DateTimeInterface
    {
        return $this->nsiaDataDelibera;
    }

    public function setNsiaDataDelibera(?\DateTimeInterface $nsiaDataDelibera): self
    {
        $this->nsiaDataDelibera = $nsiaDataDelibera;

        return $this;
    }

    public function getNsiaCodiceCor(): ?int
    {
        return $this->nsiaCodiceCor;
    }

    public function setNsiaCodiceCor(?int $nsiaCodiceCor): self
    {
        $this->nsiaCodiceCor = $nsiaCodiceCor;

        return $this;
    }

    public function getNsiaDataRilascioCor(): ?\DateTimeInterface
    {
        return $this->nsiaDataRilascioCor;
    }

    public function setNsiaDataRilascioCor(?\DateTimeInterface $nsiaDataRilascioCor): self
    {
        $this->nsiaDataRilascioCor = $nsiaDataRilascioCor;

        return $this;
    }

    public function getNsiaLDclImportoRiscatto(): ?float
    {
        return $this->nsiaLDclImportoRiscatto;
    }

    public function setNsiaLDclImportoRiscatto(?float $nsiaLDclImportoRiscatto): self
    {
        $this->nsiaLDclImportoRiscatto = $nsiaLDclImportoRiscatto;

        return $this;
    }

    public function getNsiaDurataGaranzia(): ?int
    {
        return $this->nsiaDurataGaranzia;
    }

    public function setNsiaDurataGaranzia(?int $nsiaDurataGaranzia): self
    {
        $this->nsiaDurataGaranzia = $nsiaDurataGaranzia;

        return $this;
    }

    public function getNsiaImportoRiassicurazione(): ?float
    {
        return $this->nsiaImportoRiassicurazione;
    }

    public function setNsiaImportoRiassicurazione(?float $nsiaImportoRiassicurazione): self
    {
        $this->nsiaImportoRiassicurazione = $nsiaImportoRiassicurazione;

        return $this;
    }

    public function getNsiaEslRiassicurazione(): ?float
    {
        return $this->nsiaEslRiassicurazione;
    }

    public function setNsiaEslRiassicurazione(?float $nsiaEslRiassicurazione): self
    {
        $this->nsiaEslRiassicurazione = $nsiaEslRiassicurazione;

        return $this;
    }

    public function getNsiaDataInizioGaranzia(): ?\DateTimeInterface
    {
        return $this->nsiaDataInizioGaranzia;
    }

    public function setNsiaDataInizioGaranzia(?\DateTimeInterface $nsiaDataInizioGaranzia): self
    {
        $this->nsiaDataInizioGaranzia = $nsiaDataInizioGaranzia;

        return $this;
    }

    public function getNsiaDataFineGaranzia(): ?\DateTimeInterface
    {
        return $this->nsiaDataFineGaranzia;
    }

    public function setNsiaDataFineGaranzia(?\DateTimeInterface $nsiaDataFineGaranzia): self
    {
        $this->nsiaDataFineGaranzia = $nsiaDataFineGaranzia;

        return $this;
    }

    public function getNsiaDataLiquidazioneConfidi(): ?\DateTimeInterface
    {
        return $this->nsiaDataLiquidazioneConfidi;
    }

    public function setNsiaDataLiquidazioneConfidi(?\DateTimeInterface $nsiaDataLiquidazioneConfidi): self
    {
        $this->nsiaDataLiquidazioneConfidi = $nsiaDataLiquidazioneConfidi;

        return $this;
    }

    public function getNsiaImportoPerditaConfidi(): ?float
    {
        return $this->nsiaImportoPerditaConfidi;
    }

    public function setNsiaImportoPerditaConfidi(?float $nsiaImportoPerditaConfidi): self
    {
        $this->nsiaImportoPerditaConfidi = $nsiaImportoPerditaConfidi;

        return $this;
    }

    public function getNsiaDataRichiestaRimborso(): ?\DateTimeInterface
    {
        return $this->nsiaDataRichiestaRimborso;
    }

    public function setNsiaDataRichiestaRimborso(?\DateTimeInterface $nsiaDataRichiestaRimborso): self
    {
        $this->nsiaDataRichiestaRimborso = $nsiaDataRichiestaRimborso;

        return $this;
    }

    public function getNsiaDataProtocolloPerdita(): ?\DateTimeInterface
    {
        return $this->nsiaDataProtocolloPerdita;
    }

    public function setNsiaDataProtocolloPerdita(?\DateTimeInterface $nsiaDataProtocolloPerdita): self
    {
        $this->nsiaDataProtocolloPerdita = $nsiaDataProtocolloPerdita;

        return $this;
    }

    public function getNsiaDataDeliberaPerdita(): ?\DateTimeInterface
    {
        return $this->nsiaDataDeliberaPerdita;
    }

    public function setNsiaDataDeliberaPerdita(?\DateTimeInterface $nsiaDataDeliberaPerdita): self
    {
        $this->nsiaDataDeliberaPerdita = $nsiaDataDeliberaPerdita;

        return $this;
    }

    public function getNsiaImportoRimborsoPrenotato(): ?float
    {
        return $this->nsiaImportoRimborsoPrenotato;
    }

    public function setNsiaImportoRimborsoPrenotato(?float $nsiaImportoRimborsoPrenotato): self
    {
        $this->nsiaImportoRimborsoPrenotato = $nsiaImportoRimborsoPrenotato;

        return $this;
    }

    public function getNsiaImportoRimborsato(): ?float
    {
        return $this->nsiaImportoRimborsato;
    }

    public function setNsiaImportoRimborsato(?float $nsiaImportoRimborsato): self
    {
        $this->nsiaImportoRimborsato = $nsiaImportoRimborsato;

        return $this;
    }

    public function getNsiaDataLiquidazione(): ?\DateTimeInterface
    {
        return $this->nsiaDataLiquidazione;
    }

    public function setNsiaDataLiquidazione(?\DateTimeInterface $nsiaDataLiquidazione): self
    {
        $this->nsiaDataLiquidazione = $nsiaDataLiquidazione;

        return $this;
    }

    public function getNsiaImportoRestituitoConfidi(): ?float
    {
        return $this->nsiaImportoRestituitoConfidi;
    }

    public function setNsiaImportoRestituitoConfidi(?float $nsiaImportoRestituitoConfidi): self
    {
        $this->nsiaImportoRestituitoConfidi = $nsiaImportoRestituitoConfidi;

        return $this;
    }

    public function getNsiaDataRestituzioneConfidi(): ?\DateTimeInterface
    {
        return $this->nsiaDataRestituzioneConfidi;
    }

    public function setNsiaDataRestituzioneConfidi(?\DateTimeInterface $nsiaDataRestituzioneConfidi): self
    {
        $this->nsiaDataRestituzioneConfidi = $nsiaDataRestituzioneConfidi;

        return $this;
    }

    public function canBeDeleted(): bool
    {
        return
            !$this->getProtocolDate() &&
            $this->getStatus() !== self::STATUS_REGISTERED &&
            $this->getStatus() !== self::STATUS_LINKED
            ;
    }

//TODO: campi virtuali
//    const CONTRACT_TYPE_FINANCING = 'finanziamento';
//    const CONTRACT_TYPE_LEASING = 'leasing';
    public function getContrattoTipo(): ?string
    {
        $hasFinancing = $this->getApplicationImport()->getFinancingImports()->filter(function (FinancingImport $financingImport) {
//                return $financingImport->getPracticeId() === $this->getPracticeId();
                return strtolower($financingImport->getPracticeId()) === strtolower($this->getPracticeId());
            })->count() > 0;
        $hasLeasing = $this->getApplicationImport()->getLeasingImports()->filter(function (LeasingImport $leasingImport) {
//                return $leasingImport->getPracticeId() === $this->getPracticeId();
                return strtolower($leasingImport->getPracticeId()) === strtolower($this->getPracticeId());
            })->count() > 0;

        if ($hasFinancing) {
            return self::CONTRACT_TYPE_FINANCING;
        }
        if ($hasLeasing) {
            return self::CONTRACT_TYPE_LEASING;
        }
        return null;
    }

    public function getContrattoDataErogazione(): ?\DateTimeInterface
    {
        $type = $this->getContrattoTipo();
        if ($type === self::CONTRACT_TYPE_FINANCING) {
            return $this->getFDfIssueDate();
        }
//        if ($type === self::CONTRACT_TYPE_LEASING) {
//            return $this->getXXXX();
//        }
        return null;
    }

    public function getContrattoDataFirma(): ?\DateTimeInterface
    {
        $type = $this->getContrattoTipo();
        if ($type === self::CONTRACT_TYPE_FINANCING) {
            return $this->getFDfContractSignatureDate();
        }
        if ($type === self::CONTRACT_TYPE_LEASING) {
            return $this->getLDclContractSignatureDate();
        }
        return null;
    }

    public function getContrattoFinalita(): ?string
    {
        $type = $this->getContrattoTipo();
        if ($type === self::CONTRACT_TYPE_FINANCING) {
            return $this->getFFinancialDestination();
        }
        if ($type === self::CONTRACT_TYPE_LEASING) {
            return $this->getLSfLeasingDestination();
        }
        return null;
    }

    public function getContrattoImporto(): ?float
    {
        $type = $this->getContrattoTipo();
        if ($type === self::CONTRACT_TYPE_FINANCING) {
            return $this->getFDfAmount();
        }
        if ($type === self::CONTRACT_TYPE_LEASING) {
            return $this->getLDclAmount();
        }
        return null;
    }

    public function getContrattoDurataMesi(): ?int
    {
        $type = $this->getContrattoTipo();
        if ($type === self::CONTRACT_TYPE_FINANCING) {
            return $this->getFDfDuration();
        }
        if ($type === self::CONTRACT_TYPE_LEASING) {
            return $this->getLDclDuration();
        }
        return null;
    }

    public function getFinancingProvisioningCertification(): ?FinancingProvisioningCertification
    {
        return $this->financingProvisioningCertification;
    }

    public function setFinancingProvisioningCertification(?FinancingProvisioningCertification $financingProvisioningCertification): self
    {
        // unset the owning side of the relation if necessary
        if ($financingProvisioningCertification === null && $this->financingProvisioningCertification !== null) {
            $this->financingProvisioningCertification->setApplication(null);
        }

        // set the owning side of the relation if necessary
        if ($financingProvisioningCertification !== null && $financingProvisioningCertification->getApplication() !== $this) {
            $financingProvisioningCertification->setApplication($this);
        }

        $this->financingProvisioningCertification = $financingProvisioningCertification;

        return $this;
    }
}
