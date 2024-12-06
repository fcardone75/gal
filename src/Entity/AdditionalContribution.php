<?php

namespace App\Entity;

use App\Repository\AdditionalContributionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=AdditionalContributionRepository::class)
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true, hardDelete=false)
 */
class AdditionalContribution
{
    const TYPE_ABB = 'ABB';
    const TYPE_CON = 'CON';
    const TYPE_CFP = 'CFP';

    const STATUS_NSIA_00100 = '00100';
    const STATUS_NSIA_00101 = '00101';
    const STATUS_NSIA_00102 = '00102';
    const STATUS_NSIA_00103 = '00103';
    const STATUS_NSIA_00104 = '00104';
    const STATUS_NSIA_00106 = '00106';

    const STATUS_NSIA_00300 = '00300';
    const STATUS_NSIA_00301 = '00301';
    const STATUS_NSIA_00302 = '00302';

    const STATUS_NSIA_00110 = '00110';

    const STATUS_NSIA_00111 = '00111';
    const STATUS_NSIA_00303 = '00303';
    const STATUS_NSIA_00304 = '00304';
    const STATUS_NSIA_00305 = '00305';
    const STATUS_NSIA_00306 = '00306';
    const STATUS_NSIA_00307 = '00307';
    const STATUS_NSIA_00308 = '00308';
    const STATUS_NSIA_00310 = '00310';
    const STATUS_NSIA_00311 = '00311';
    const STATUS_NSIA_00312 = '00312';
    const STATUS_NSIA_00315 = '00315';

// stati contributo aggiuntivo
    public static $statusesNsiaMap = [
        '00100' => [
            'status' => self::STATUS_NSIA_00100, // 'In istruttoria',
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
            'status' => self::STATUS_NSIA_00104, // 'Deliberata Positiva, in attesa di antimafia',
            'note' => false
        ],
        '00106' => [
            'status' => self::STATUS_NSIA_00106, // 'Annullata',
            'note' => true
        ],

        '00300' => [
            'status' => self::STATUS_NSIA_00300, // 'Deliberata Positiva',
            'note' => false
        ],
        '00301' => [
            'status' => self::STATUS_NSIA_00301, // 'Deliberata positivamente, in attesa di verifica documentale', // 'Delibera positiva - sospesa per lavorazioni'
            'note' => false
        ],
        '00302' => [
            'status' => self::STATUS_NSIA_00302, // 'Deliberata positivamente, in attesa di perfezionamento del finanziamento',
            'note' => false
        ],

'00110' => [
    'status' => self::STATUS_NSIA_00110, // 'Perfezionata',
    'note' => false
],

        '00111' => [
            'status' => self::STATUS_NSIA_00111, // 'Deliberata Positiva, in attesa di perfezionamento',
            'note' => false
        ],
        '00303' => [
            'status' => self::STATUS_NSIA_00303, // 'Pratica perfezionata, in attesa di antimafia',
            'note' => false
        ],
        '00304' => [
            'status' => self::STATUS_NSIA_00304, // 'Pratica Perfezionata - sospesa per lavorazioni',
            'note' => false
        ],
        '00305' => [
            'status' => self::STATUS_NSIA_00305, // 'Liquidata',
            'note' => false
        ],
        '00306' => [
            'status' => self::STATUS_NSIA_00306, // 'Liquidata parzialmente',
            'note' => false
        ],
        '00307' => [
            'status' => self::STATUS_NSIA_00307, // 'Revocata',
            'note' => true
        ],
        '00308' => [
            'status' => self::STATUS_NSIA_00308, // 'Parzialmente Revocata',
            'note' => true
        ],
        '00310' => [
            'status' => self::STATUS_NSIA_00310, // 'Recupero in corso',
            'note' => true
        ],
        '00311' => [
            'status' => self::STATUS_NSIA_00311, // 'Recupero in corso da parte di Equitalia',
            'note' => true
        ],
        '00312' => [
            'status' => self::STATUS_NSIA_00312, // 'Recupero parzialmente restituito',
            'note' => true
        ],
        '00315' => [
            'status' => self::STATUS_NSIA_00315, // 'Recupero Restituito',
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
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="date")
     */
    private $presentationDate;

    /**
     * @ORM\ManyToOne(targetEntity=Application::class, inversedBy="additionalContributions")
     */
    private $application;

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
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $inImport = false;

    /**
     * @ORM\ManyToOne(targetEntity=RegistryFileAudit::class)
     */
    private $registryFileAudit;


    private $formOrderBy;

    public static $formOrderByMap = [
        self::TYPE_ABB => 1,
        self::TYPE_CON => 2,
        self::TYPE_CFP => 3
    ];

    public function __construct()
    {
        $this->presentationDate = new \DateTime();
    }



    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $nsiaNumeroPosizione;
//getNsiaNumeroPosizione
//setNsiaNumeroPosizione


    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $nsiaStatus;
//getNsiaStatus
//setNsiaStatus

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $nsiaNota;
//getNsiaNota
//setNsiaNota

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataDelibera;
//getNsiaDataDelibera
//setNsiaDataDelibera

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nsiaCodiceCor;
//getNsiaCodiceCor
//setNsiaCodiceCor

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataRilascioCor;
//getNsiaDataRilascioCor
//setNsiaDataRilascioCor

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaImportoContributoDeliberato;
//getNsiaImportoContributoDeliberato
//setNsiaImportoContributoDeliberato

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaImportoContributoLiquidato;
//getNsiaImportoContributoLiquidato
//setNsiaImportoContributoLiquidato

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataLiquidazione;
//getNsiaDataLiquidazione
//setNsiaDataLiquidazione

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $nsiaIbanLiquidazione;
//setNsiaIbanLiquidazione
//setNsiaIbanLiquidazione

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataRevoca;
//getNsiaDataRevoca
//setNsiaDataRevoca

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $nsiaMotivoRevoca;
//getNsiaMotivoRevoca
//setNsiaMotivoRevoca

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaImportoContributoRevocato;
//getNsiaImportoContributoRevocato
//setNsiaImportoContributoRevocato

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataAvvioProcedimentoRevoca;
//getNsiaDataAvvioProcedimentoRevoca
//setNsiaDataAvvioProcedimentoRevoca

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaImportoRecuperoDovuto;
//getNsiaImportoRecuperoDovuto
//setNsiaImportoRecuperoDovuto

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaInteressiDovuti;
//getNsiaInteressiDovuti
//setNsiaInteressiDovuti

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataRichiestaRecupero;
//getNsiaDataRichiestaRecupero
//setNsiaDataRichiestaRecupero

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaImportoContributoRestituito;
//getNsiaImportoContributoRestituito
//setNsiaImportoContributoRestituito

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $nsiaImportoInteressiRestituiti;
//getNsiaImportoInteressiRestituiti
//setNsiaImportoInteressiRestituiti

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $nsiaDataRestituzione;
//getNsiaDataRestituzione
//setNsiaDataRestituzione

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getPresentationDate(): ?\DateTimeInterface
    {
        return $this->presentationDate;
    }

    public function setPresentationDate(?\DateTimeInterface $presentationDate): self
    {
        $this->presentationDate = $presentationDate;

        return $this;
    }

    public function getApplication(): ?Application
    {
        return $this->application;
    }

    public function setApplication(?Application $application): self
    {
        $this->application = $application;

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

    public function getInImport(): ?bool
    {
        return $this->inImport;
    }

    public function setInImport(?bool $inImport): self
    {
        $this->inImport = $inImport;

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

    public function getFormOrderBy()
    {
        return $this->formOrderBy;
    }

    public function setFormOrderBy($formOrderBy): self
    {
        $this->formOrderBy = $formOrderBy;

        return $this;
    }


//    /**
//     * @ORM\Column(type="string", length=64, nullable=true)
//     */
//    private $nsiaNumeroPosizione;
////getNsiaNumeroPosizione
////setNsiaNumeroPosizione
    public function getNsiaNumeroPosizione(): ?string
    {
        return $this->nsiaNumeroPosizione;
    }

    public function setNsiaNumeroPosizione(string $nsiaNumeroPosizione): self
    {
        $this->nsiaNumeroPosizione = $nsiaNumeroPosizione;

        return $this;
    }

//    /**
//     * @ORM\Column(type="string", length=64, nullable=true)
//     */
//    private $nsiaStatus;
////getNsiaStatus
////setNsiaStatus
    public function getNsiaStatus(): ?string
    {
        return $this->nsiaStatus;
    }

    public function setNsiaStatus(string $nsiaStatus): self
    {
        $this->nsiaStatus = $nsiaStatus;

        return $this;
    }

//    /**
//     * @ORM\Column(type="string", nullable=true)
//     */
//    private $nsiaNota;
//getNsiaNota
//setNsiaNota
    public function getNsiaNota(): ?string
    {
        return $this->nsiaNota;
    }

    public function setNsiaNota(string $nsiaNota): self
    {
        $this->nsiaNota = $nsiaNota;

        return $this;
    }

//    /**
//     * @ORM\Column(type="date", nullable=true)
//     */
//    private $nsiaDataDelibera;
////getNsiaDataDelibera
////setNsiaDataDelibera
    public function getNsiaDataDelibera(): ?\DateTimeInterface
    {
        return $this->nsiaDataDelibera;
    }

    public function setNsiaDataDelibera(?\DateTimeInterface $nsiaDataDelibera): self
    {
        $this->nsiaDataDelibera = $nsiaDataDelibera;

        return $this;
    }

//    /**
//     * @ORM\Column(type="integer", nullable=true)
//     */
//    private $nsiaCodiceCor;
////getNsiaCodiceCor
////setNsiaCodiceCor
    public function getNsiaCodiceCor(): ?int
    {
        return $this->nsiaCodiceCor;
    }

    public function setNsiaCodiceCor(?int $nsiaCodiceCor): self
    {
        $this->nsiaCodiceCor = $nsiaCodiceCor;

        return $this;
    }

//    /**
//     * @ORM\Column(type="date", nullable=true)
//     */
//    private $nsiaDataRilascioCor;
////getNsiaDataRilascioCor
////setNsiaDataRilascioCor
    public function getNsiaDataRilascioCor(): ?\DateTimeInterface
    {
        return $this->nsiaDataRilascioCor;
    }

    public function setNsiaDataRilascioCor(?\DateTimeInterface $nsiaDataRilascioCor): self
    {
        $this->nsiaDataRilascioCor = $nsiaDataRilascioCor;

        return $this;
    }

//    /**
//     * @ORM\Column(type="float", nullable=true)
//     */
//    private $nsiaImportoContributoDeliberato;
////getNsiaImportoContributoDeliberato
////setNsiaImportoContributoDeliberato
    public function getNsiaImportoContributoDeliberato(): ?float
    {
        return $this->nsiaImportoContributoDeliberato;
    }

    public function setNsiaImportoContributoDeliberato(?float $nsiaImportoContributoDeliberato): self
    {
        $this->nsiaImportoContributoDeliberato = $nsiaImportoContributoDeliberato;

        return $this;
    }

//    /**
//     * @ORM\Column(type="float", nullable=true)
//     */
//    private $nsiaImportoContributoLiquidato;
////getNsiaImportoContributoLiquidato
////setNsiaImportoContributoLiquidato
    public function getNsiaImportoContributoLiquidato(): ?float
    {
        return $this->nsiaImportoContributoLiquidato;
    }

    public function setNsiaImportoContributoLiquidato(?float $nsiaImportoContributoLiquidato): self
    {
        $this->nsiaImportoContributoLiquidato = $nsiaImportoContributoLiquidato;

        return $this;
    }

//    /**
//     * @ORM\Column(type="date", nullable=true)
//     */
//    private $nsiaDataLiquidazione;
////getNsiaDataLiquidazione
////setNsiaDataLiquidazione
    public function getNsiaDataLiquidazione(): ?\DateTimeInterface
    {
        return $this->nsiaDataLiquidazione;
    }

    public function setNsiaDataLiquidazione(?\DateTimeInterface $nsiaDataLiquidazione): self
    {
        $this->nsiaDataLiquidazione = $nsiaDataLiquidazione;

        return $this;
    }

//    /**
//     * @ORM\Column(type="string", nullable=true)
//     */
//    private $nsiaIbanLiquidazione;
////setNsiaIbanLiquidazione
////setNsiaIbanLiquidazione
    public function getNsiaIbanLiquidazione(): ?string
    {
        return $this->nsiaIbanLiquidazione;
    }

    public function setNsiaIbanLiquidazione(string $nsiaIbanLiquidazione): self
    {
        $this->nsiaIbanLiquidazione = $nsiaIbanLiquidazione;

        return $this;
    }

//    /**
//     * @ORM\Column(type="date", nullable=true)
//     */
//    private $nsiaDataRevoca;
////getNsiaDataRevoca
////setNsiaDataRevoca
    public function getNsiaDataRevoca(): ?\DateTimeInterface
    {
        return $this->nsiaDataRevoca;
    }

    public function setNsiaDataRevoca(?\DateTimeInterface $nsiaDataRevoca): self
    {
        $this->nsiaDataRevoca = $nsiaDataRevoca;

        return $this;
    }

//    /**
//     * @ORM\Column(type="string", nullable=true)
//     */
//    private $nsiaMotivoRevoca;
////getNsiaMotivoRevoca
////setNsiaMotivoRevoca
    public function getNsiaMotivoRevoca(): ?string
    {
        return $this->nsiaMotivoRevoca;
    }

    public function setNsiaMotivoRevoca(string $nsiaMotivoRevoca): self
    {
        $this->nsiaMotivoRevoca = $nsiaMotivoRevoca;

        return $this;
    }

//    /**
//     * @ORM\Column(type="float", nullable=true)
//     */
//    private $nsiaImportoContributoRevocato;
////getNsiaImportoContributoRevocato
////setNsiaImportoContributoRevocato
    public function getNsiaImportoContributoRevocato(): ?float
    {
        return $this->nsiaImportoContributoRevocato;
    }

    public function setNsiaImportoContributoRevocato(?float $nsiaImportoContributoRevocato): self
    {
        $this->nsiaImportoContributoRevocato = $nsiaImportoContributoRevocato;

        return $this;
    }

//    /**
//     * @ORM\Column(type="date", nullable=true)
//     */
//    private $nsiaDataAvvioProcedimentoRevoca;
////getNsiaDataAvvioProcedimentoRevoca
////setNsiaDataAvvioProcedimentoRevoca
    public function getNsiaDataAvvioProcedimentoRevoca(): ?\DateTimeInterface
    {
        return $this->nsiaDataAvvioProcedimentoRevoca;
    }

    public function setNsiaDataAvvioProcedimentoRevoca(?\DateTimeInterface $nsiaDataAvvioProcedimentoRevoca): self
    {
        $this->nsiaDataAvvioProcedimentoRevoca = $nsiaDataAvvioProcedimentoRevoca;

        return $this;
    }

//    /**
//     * @ORM\Column(type="float", nullable=true)
//     */
//    private $nsiaImportoRecuperoDovuto;
////getNsiaImportoRecuperoDovuto
////setNsiaImportoRecuperoDovuto
    public function getNsiaImportoRecuperoDovuto(): ?float
    {
        return $this->nsiaImportoRecuperoDovuto;
    }

    public function setNsiaImportoRecuperoDovuto(?float $nsiaImportoRecuperoDovuto): self
    {
        $this->nsiaImportoRecuperoDovuto = $nsiaImportoRecuperoDovuto;

        return $this;
    }

//    /**
//     * @ORM\Column(type="float", nullable=true)
//     */
//    private $nsiaInteressiDovuti;
////getNsiaInteressiDovuti
////setNsiaInteressiDovuti
    public function getNsiaInteressiDovuti(): ?float
    {
        return $this->nsiaInteressiDovuti;
    }

    public function setNsiaInteressiDovuti(?float $nsiaInteressiDovuti): self
    {
        $this->nsiaInteressiDovuti = $nsiaInteressiDovuti;

        return $this;
    }

//    /**
//     * @ORM\Column(type="date", nullable=true)
//     */
//    private $nsiaDataRichiestaRecupero;
////getNsiaDataRichiestaRecupero
////setNsiaDataRichiestaRecupero
    public function getNsiaDataRichiestaRecupero(): ?\DateTimeInterface
    {
        return $this->nsiaDataRichiestaRecupero;
    }

    public function setNsiaDataRichiestaRecupero(?\DateTimeInterface $nsiaDataRichiestaRecupero): self
    {
        $this->nsiaDataRichiestaRecupero = $nsiaDataRichiestaRecupero;

        return $this;
    }

//    /**
//     * @ORM\Column(type="float", nullable=true)
//     */
//    private $nsiaImportoContributoRestituito;
////getNsiaImportoContributoRestituito
////setNsiaImportoContributoRestituito
    public function getNsiaImportoContributoRestituito(): ?float
    {
        return $this->nsiaImportoContributoRestituito;
    }

    public function setNsiaImportoContributoRestituito(?float $nsiaImportoContributoRestituito): self
    {
        $this->nsiaImportoContributoRestituito = $nsiaImportoContributoRestituito;

        return $this;
    }

//    /**
//     * @ORM\Column(type="float", nullable=true)
//     */
//    private $nsiaImportoInteressiRestituiti;
////getNsiaImportoInteressiRestituiti
////setNsiaImportoInteressiRestituiti
    public function getNsiaImportoInteressiRestituiti(): ?float
    {
        return $this->nsiaImportoInteressiRestituiti;
    }

    public function setNsiaImportoInteressiRestituiti(?float $nsiaImportoInteressiRestituiti): self
    {
        $this->nsiaImportoInteressiRestituiti = $nsiaImportoInteressiRestituiti;

        return $this;
    }

//    /**
//     * @ORM\Column(type="date", nullable=true)
//     */
//    private $nsiaDataRestituzione;
////getNsiaDataRestituzione
////setNsiaDataRestituzione
    public function getNsiaDataRestituzione(): ?\DateTimeInterface
    {
        return $this->nsiaDataRestituzione;
    }

    public function setNsiaDataRestituzione(?\DateTimeInterface $nsiaDataRestituzione): self
    {
        $this->nsiaDataRestituzione = $nsiaDataRestituzione;

        return $this;
    }

}
