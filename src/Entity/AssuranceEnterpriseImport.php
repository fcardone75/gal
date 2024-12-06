<?php

namespace App\Entity;

use App\Repository\AssuranceEnterpriseImportRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints\Iban;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=AssuranceEnterpriseImportRepository::class)
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=false)
 */
class AssuranceEnterpriseImport
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * (strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ApplicationImport", inversedBy="assuranceEnterpriseImports")
     */
    private $applicationImport;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $practiceId;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $flagEnergia;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $gAssuranceAmount;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $gResolutionDate;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $acCommissionsRebateRequest;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $acInterestsContributionRequest;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $acLostFundContributionRequest;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $acContributionApplicationAmount;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    private $acApplicationMembers;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ibBusinessName;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $ibFiscalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ibSize;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $ibConstitutionDate;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $ibChamberOfCommerceCode;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $ibChamberOfCommerceRegistrationDate;

    /**
     * @ORM\Column(type="string", length=9, nullable=true)
     */
    private $ibAIACode;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $ibAIARegistrationDate;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $ibAtecoCode;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $ibAtecoStartDate;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $ibIban;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ibLegalForm;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pecAddress;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $officeAddress;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    private $officePostcode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $officeCity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $workplaceAddress;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    private $workplacePostcode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $workplaceCity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ownerLastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ownerFirstname;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $ownerBirthDate;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $ownerGender;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $ownerFiscalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ownerBirthCity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ownerBirthCountry;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $ownerJoinDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstMemberLastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstMemberFirstname;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $firstMemberBirthDate;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $firstMemberGender;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $firstMemberFiscalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstMemberBirthCity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstMemberBirthCountry;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $firstMemberJoinDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $secondMemberLastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $secondMemberFirstname;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $secondMemberBirthDate;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $secondMemberGender;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $secondMemberFiscalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $secondMemberBirthCity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $secondMemberBirthCountry;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $secondMemberJoinDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $thirdMemberLastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $thirdMemberFirstname;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $thirdMemberBirthDate;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $thirdMemberGender;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $thirdMemberFiscalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $thirdMemberBirthCity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $thirdMemberBirthCountry;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $thirdMemberJoinDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fourthMemberLastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fourthMemberFirstname;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $fourthMemberBirthDate;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $fourthMemberGender;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $fourthMemberFiscalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fourthMemberBirthCity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fourthMemberBirthCountry;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $fourthMemberJoinDate;

    /**
     * @ORM\Column(type="integer", name="`row`", nullable=true)
     */
    private $row;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplicationImport(): ?ApplicationImport
    {
        return $this->applicationImport;
    }

    public function setApplicationImport(ApplicationImport $applicationImport): self
    {
        $this->applicationImport = $applicationImport;

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

    public function getGAssuranceAmount(): ?string
    {
        return $this->gAssuranceAmount;
    }

    public function setGAssuranceAmount(string $gAssuranceAmount): self
    {
        $this->gAssuranceAmount = $gAssuranceAmount;

        return $this;
    }

    public function getGResolutionDate(): ?string
    {
        return $this->gResolutionDate;
    }

    public function setGResolutionDate(?string $gResolutionDate): self
    {
        $this->gResolutionDate = $gResolutionDate;

        return $this;
    }

    public function getAcCommissionsRebateRequest(): ?string
    {
        return $this->acCommissionsRebateRequest;
    }

    public function setAcCommissionsRebateRequest(?string $acCommissionsRebateRequest): self
    {
        $this->acCommissionsRebateRequest = $acCommissionsRebateRequest;

        return $this;
    }

    public function getAcInterestsContributionRequest(): ?string
    {
        return $this->acInterestsContributionRequest;
    }

    public function setAcInterestsContributionRequest(?string $acInterestsContributionRequest): self
    {
        $this->acInterestsContributionRequest = $acInterestsContributionRequest;

        return $this;
    }

    public function getAcLostFundContributionRequest(): ?string
    {
        return $this->acLostFundContributionRequest;
    }

    public function setAcLostFundContributionRequest(?string $acLostFundContributionRequest): self
    {
        $this->acLostFundContributionRequest = $acLostFundContributionRequest;

        return $this;
    }

    public function getAcContributionApplicationAmount(): ?string
    {
        return $this->acContributionApplicationAmount;
    }

    public function setAcContributionApplicationAmount(?string $acContributionApplicationAmount): self
    {
        $this->acContributionApplicationAmount = $acContributionApplicationAmount;

        return $this;
    }

    public function getAcApplicationMembers(): ?string
    {
        return $this->acApplicationMembers;
    }

    public function setAcApplicationMembers(?string $acApplicationMembers): self
    {
        $this->acApplicationMembers = $acApplicationMembers;

        return $this;
    }

    public function getIbBusinessName(): ?string
    {
        return $this->ibBusinessName;
    }

    public function setIbBusinessName(string $ibBusinessName): self
    {
        $this->ibBusinessName = $ibBusinessName;

        return $this;
    }

    public function getIbFiscalCode(): ?string
    {
        return $this->ibFiscalCode;
    }

    public function setIbFiscalCode(string $ibFiscalCode): self
    {
        $this->ibFiscalCode = $ibFiscalCode;

        return $this;
    }

    public function getIbSize(): ?string
    {
        return $this->ibSize;
    }

    public function setIbSize(?string $ibSize): self
    {
        $this->ibSize = $ibSize;

        return $this;
    }

    public function getIbConstitutionDate(): ?string
    {
        return $this->ibConstitutionDate;
    }

    public function setIbConstitutionDate(?string $ibConstitutionDate): self
    {
        $this->ibConstitutionDate = $ibConstitutionDate;

        return $this;
    }

    public function getIbChamberOfCommerceCode(): ?string
    {
        return $this->ibChamberOfCommerceCode;
    }

    public function setIbChamberOfCommerceCode(string $ibChamberOfCommerceCode): self
    {
        $this->ibChamberOfCommerceCode = $ibChamberOfCommerceCode;

        return $this;
    }

    public function getIbChamberOfCommerceRegistrationDate(): ?string
    {
        return $this->ibChamberOfCommerceRegistrationDate;
    }

    public function setIbChamberOfCommerceRegistrationDate(?string $ibChamberOfCommerceRegistrationDate): self
    {
        $this->ibChamberOfCommerceRegistrationDate = $ibChamberOfCommerceRegistrationDate;

        return $this;
    }

    public function getIbAIACode(): ?string
    {
        return $this->ibAIACode;
    }

    public function setIbAIACode(string $ibAIACode): self
    {
        $this->ibAIACode = $ibAIACode;

        return $this;
    }

    public function getIbAIARegistrationDate(): ?string
    {
        return $this->ibAIARegistrationDate;
    }

    public function setIbAIARegistrationDate(?string $ibAIARegistrationDate): self
    {
        $this->ibAIARegistrationDate = $ibAIARegistrationDate;

        return $this;
    }

    public function getIbAtecoCode(): ?string
    {
        return $this->ibAtecoCode;
    }

    public function setIbAtecoCode(?string $ibAtecoCode): self
    {
        $this->ibAtecoCode = $ibAtecoCode;

        return $this;
    }

    public function getIbAtecoStartDate(): ?string
    {
        return $this->ibAtecoStartDate;
    }

    public function setIbAtecoStartDate(?string $ibAtecoStartDate): self
    {
        $this->ibAtecoStartDate = $ibAtecoStartDate;

        return $this;
    }

    public function getIbIban(): ?string
    {
        return $this->ibIban;
    }

    public function setIbIban(?string $ibIban): self
    {
        $this->ibIban = $ibIban;

        return $this;
    }

    public function getIbLegalForm(): ?string
    {
        return $this->ibLegalForm;
    }

    public function setIbLegalForm(?string $ibLegalForm): self
    {
        $this->ibLegalForm = $ibLegalForm;

        return $this;
    }

    public function getPecAddress(): ?string
    {
        return $this->pecAddress;
    }

    public function setPecAddress(?string $pecAddress): self
    {
        $this->pecAddress = $pecAddress;

        return $this;
    }

    public function getOfficeAddress(): ?string
    {
        return $this->officeAddress;
    }

    public function setOfficeAddress(?string $officeAddress): self
    {
        $this->officeAddress = $officeAddress;

        return $this;
    }

    public function getOfficePostcode(): ?string
    {
        return $this->officePostcode;
    }

    public function setOfficePostcode(?string $officePostcode): self
    {
        $this->officePostcode = $officePostcode;

        return $this;
    }

    public function getOfficeCity(): ?string
    {
        return $this->officeCity;
    }

    public function setOfficeCity(?string $officeCity): self
    {
        $this->officeCity = $officeCity;

        return $this;
    }

    public function getWorkplaceAddress(): ?string
    {
        return $this->workplaceAddress;
    }

    public function setWorkplaceAddress(?string $workplaceAddress): self
    {
        $this->workplaceAddress = $workplaceAddress;

        return $this;
    }

    public function getWorkplacePostcode(): ?string
    {
        return $this->workplacePostcode;
    }

    public function setWorkplacePostcode(?string $workplacePostcode): self
    {
        $this->workplacePostcode = $workplacePostcode;

        return $this;
    }

    public function getWorkplaceCity(): ?string
    {
        return $this->workplaceCity;
    }

    public function setWorkplaceCity(?string $workplaceCity): self
    {
        $this->workplaceCity = $workplaceCity;

        return $this;
    }

    public function getOwnerLastname(): ?string
    {
        return $this->ownerLastname;
    }

    public function setOwnerLastname(?string $ownerLastname): self
    {
        $this->ownerLastname = $ownerLastname;

        return $this;
    }

    public function getOwnerFirstname(): ?string
    {
        return $this->ownerFirstname;
    }

    public function setOwnerFirstname(?string $ownerFirstname): self
    {
        $this->ownerFirstname = $ownerFirstname;

        return $this;
    }

    public function getOwnerBirthDate(): ?string
    {
        return $this->ownerBirthDate;
    }

    public function setOwnerBirthDate(?string $ownerBirthDate): self
    {
        $this->ownerBirthDate = $ownerBirthDate;

        return $this;
    }

    public function getOwnerGender(): ?string
    {
        return $this->ownerGender;
    }

    public function setOwnerGender(?string $ownerGender): self
    {
        $this->ownerGender = $ownerGender;

        return $this;
    }

    public function getOwnerFiscalCode(): ?string
    {
        return $this->ownerFiscalCode;
    }

    public function setOwnerFiscalCode(?string $ownerFiscalCode): self
    {
        $this->ownerFiscalCode = $ownerFiscalCode;

        return $this;
    }

    public function getOwnerBirthCity(): ?string
    {
        return $this->ownerBirthCity;
    }

    public function setOwnerBirthCity(?string $ownerBirthCity): self
    {
        $this->ownerBirthCity = $ownerBirthCity;

        return $this;
    }

    public function getOwnerBirthCountry(): ?string
    {
        return $this->ownerBirthCountry;
    }

    public function setOwnerBirthCountry(?string $ownerBirthCountry): self
    {
        $this->ownerBirthCountry = $ownerBirthCountry;

        return $this;
    }

    public function getOwnerJoinDate(): ?string
    {
        return $this->ownerJoinDate;
    }

    public function setOwnerJoinDate(?string $ownerJoinDate): self
    {
        $this->ownerJoinDate = $ownerJoinDate;

        return $this;
    }

    public function getFirstMemberLastname(): ?string
    {
        return $this->firstMemberLastname;
    }

    public function setFirstMemberLastname(?string $firstMemberLastname): self
    {
        $this->firstMemberLastname = $firstMemberLastname;

        return $this;
    }

    public function getFirstMemberFirstname(): ?string
    {
        return $this->firstMemberFirstname;
    }

    public function setFirstMemberFirstname(?string $firstMemberFirstname): self
    {
        $this->firstMemberFirstname = $firstMemberFirstname;

        return $this;
    }

    public function getFirstMemberBirthDate(): ?string
    {
        return $this->firstMemberBirthDate;
    }

    public function setFirstMemberBirthDate(?string $firstMemberBirthDate): self
    {
        $this->firstMemberBirthDate = $firstMemberBirthDate;

        return $this;
    }

    public function getFirstMemberGender(): ?string
    {
        return $this->firstMemberGender;
    }

    public function setFirstMemberGender(?string $firstMemberGender): self
    {
        $this->firstMemberGender = $firstMemberGender;

        return $this;
    }

    public function getFirstMemberFiscalCode(): ?string
    {
        return $this->firstMemberFiscalCode;
    }

    public function setFirstMemberFiscalCode(?string $firstMemberFiscalCode): self
    {
        $this->firstMemberFiscalCode = $firstMemberFiscalCode;

        return $this;
    }

    public function getFirstMemberBirthCity(): ?string
    {
        return $this->firstMemberBirthCity;
    }

    public function setFirstMemberBirthCity(?string $firstMemberBirthCity): self
    {
        $this->firstMemberBirthCity = $firstMemberBirthCity;

        return $this;
    }

    public function getFirstMemberBirthCountry(): ?string
    {
        return $this->firstMemberBirthCountry;
    }

    public function setFirstMemberBirthCountry(?string $firstMemberBirthCountry): self
    {
        $this->firstMemberBirthCountry = $firstMemberBirthCountry;

        return $this;
    }

    public function getFirstMemberJoinDate(): ?string
    {
        return $this->firstMemberJoinDate;
    }

    public function setFirstMemberJoinDate(?string $firstMemberJoinDate): self
    {
        $this->firstMemberJoinDate = $firstMemberJoinDate;

        return $this;
    }

    public function getSecondMemberLastname(): ?string
    {
        return $this->secondMemberLastname;
    }

    public function setSecondMemberLastname(?string $secondMemberLastname): self
    {
        $this->secondMemberLastname = $secondMemberLastname;

        return $this;
    }

    public function getSecondMemberFirstname(): ?string
    {
        return $this->secondMemberFirstname;
    }

    public function setSecondMemberFirstname(?string $secondMemberFirstname): self
    {
        $this->secondMemberFirstname = $secondMemberFirstname;

        return $this;
    }

    public function getSecondMemberBirthDate(): ?string
    {
        return $this->secondMemberBirthDate;
    }

    public function setSecondMemberBirthDate(?string $secondMemberBirthDate): self
    {
        $this->secondMemberBirthDate = $secondMemberBirthDate;

        return $this;
    }

    public function getSecondMemberGender(): ?string
    {
        return $this->secondMemberGender;
    }

    public function setSecondMemberGender(?string $secondMemberGender): self
    {
        $this->secondMemberGender = $secondMemberGender;

        return $this;
    }

    public function getSecondMemberFiscalCode(): ?string
    {
        return $this->secondMemberFiscalCode;
    }

    public function setSecondMemberFiscalCode(?string $secondMemberFiscalCode): self
    {
        $this->secondMemberFiscalCode = $secondMemberFiscalCode;

        return $this;
    }

    public function getSecondMemberBirthCity(): ?string
    {
        return $this->secondMemberBirthCity;
    }

    public function setSecondMemberBirthCity(?string $secondMemberBirthCity): self
    {
        $this->secondMemberBirthCity = $secondMemberBirthCity;

        return $this;
    }

    public function getSecondMemberBirthCountry(): ?string
    {
        return $this->secondMemberBirthCountry;
    }

    public function setSecondMemberBirthCountry(?string $secondMemberBirthCountry): self
    {
        $this->secondMemberBirthCountry = $secondMemberBirthCountry;

        return $this;
    }

    public function getSecondMemberJoinDate(): ?string
    {
        return $this->secondMemberJoinDate;
    }

    public function setSecondMemberJoinDate(?string $secondMemberJoinDate): self
    {
        $this->secondMemberJoinDate = $secondMemberJoinDate;

        return $this;
    }

    public function getThirdMemberLastname(): ?string
    {
        return $this->thirdMemberLastname;
    }

    public function setThirdMemberLastname(?string $thirdMemberLastname): self
    {
        $this->thirdMemberLastname = $thirdMemberLastname;

        return $this;
    }

    public function getThirdMemberFirstname(): ?string
    {
        return $this->thirdMemberFirstname;
    }

    public function setThirdMemberFirstname(?string $thirdMemberFirstname): self
    {
        $this->thirdMemberFirstname = $thirdMemberFirstname;

        return $this;
    }

    public function getThirdMemberBirthDate(): ?string
    {
        return $this->thirdMemberBirthDate;
    }

    public function setThirdMemberBirthDate(?string $thirdMemberBirthDate): self
    {
        $this->thirdMemberBirthDate = $thirdMemberBirthDate;

        return $this;
    }

    public function getThirdMemberGender(): ?string
    {
        return $this->thirdMemberGender;
    }

    public function setThirdMemberGender(?string $thirdMemberGender): self
    {
        $this->thirdMemberGender = $thirdMemberGender;

        return $this;
    }

    public function getThirdMemberFiscalCode(): ?string
    {
        return $this->thirdMemberFiscalCode;
    }

    public function setThirdMemberFiscalCode(?string $thirdMemberFiscalCode): self
    {
        $this->thirdMemberFiscalCode = $thirdMemberFiscalCode;

        return $this;
    }

    public function getThirdMemberBirthCity(): ?string
    {
        return $this->thirdMemberBirthCity;
    }

    public function setThirdMemberBirthCity(?string $thirdMemberBirthCity): self
    {
        $this->thirdMemberBirthCity = $thirdMemberBirthCity;

        return $this;
    }

    public function getThirdMemberBirthCountry(): ?string
    {
        return $this->thirdMemberBirthCountry;
    }

    public function setThirdMemberBirthCountry(?string $thirdMemberBirthCountry): self
    {
        $this->thirdMemberBirthCountry = $thirdMemberBirthCountry;

        return $this;
    }

    public function getThirdMemberJoinDate(): ?string
    {
        return $this->thirdMemberJoinDate;
    }

    public function setThirdMemberJoinDate(?string $thirdMemberJoinDate): self
    {
        $this->thirdMemberJoinDate = $thirdMemberJoinDate;

        return $this;
    }

    public function getFourthMemberLastname(): ?string
    {
        return $this->fourthMemberLastname;
    }

    public function setFourthMemberLastname(?string $fourthMemberLastname): self
    {
        $this->fourthMemberLastname = $fourthMemberLastname;

        return $this;
    }

    public function getFourthMemberFirstname(): ?string
    {
        return $this->fourthMemberFirstname;
    }

    public function setFourthMemberFirstname(?string $fourthMemberFirstname): self
    {
        $this->fourthMemberFirstname = $fourthMemberFirstname;

        return $this;
    }

    public function getFourthMemberBirthDate(): ?string
    {
        return $this->fourthMemberBirthDate;
    }

    public function setFourthMemberBirthDate(?string $fourthMemberBirthDate): self
    {
        $this->fourthMemberBirthDate = $fourthMemberBirthDate;

        return $this;
    }

    public function getFourthMemberGender(): ?string
    {
        return $this->fourthMemberGender;
    }

    public function setFourthMemberGender(?string $fourthMemberGender): self
    {
        $this->fourthMemberGender = $fourthMemberGender;

        return $this;
    }

    public function getFourthMemberFiscalCode(): ?string
    {
        return $this->fourthMemberFiscalCode;
    }

    public function setFourthMemberFiscalCode(?string $fourthMemberFiscalCode): self
    {
        $this->fourthMemberFiscalCode = $fourthMemberFiscalCode;

        return $this;
    }

    public function getFourthMemberBirthCity(): ?string
    {
        return $this->fourthMemberBirthCity;
    }

    public function setFourthMemberBirthCity(?string $fourthMemberBirthCity): self
    {
        $this->fourthMemberBirthCity = $fourthMemberBirthCity;

        return $this;
    }

    public function getFourthMemberBirthCountry(): ?string
    {
        return $this->fourthMemberBirthCountry;
    }

    public function setFourthMemberBirthCountry(?string $fourthMemberBirthCountry): self
    {
        $this->fourthMemberBirthCountry = $fourthMemberBirthCountry;

        return $this;
    }

    public function getFourthMemberJoinDate(): ?string
    {
        return $this->fourthMemberJoinDate;
    }

    public function setFourthMemberJoinDate(?string $fourthMemberJoinDate): self
    {
        $this->fourthMemberJoinDate = $fourthMemberJoinDate;

        return $this;
    }

    public function validate(ExecutionContextInterface $context, $payload)
    {
        if ($this->getAcInterestsContributionRequest() === 'N' && $this->getAcLostFundContributionRequest() === 'S') {
            $context->buildViolation('assurance_enterprise_import.lost_fund_contribution_request')
                ->atPath('acLostFundContributionRequest')
                ->addViolation();
        }
        if ($this->getAcInterestsContributionRequest() === 'N' && $this->getAcContributionApplicationAmount()) {
            $context->buildViolation('assurance_enterprise_import.contribution_application_amount')
                ->atPath('acContributionApplicationAmount')
                ->addViolation();
        }
        if ($this->getAcInterestsContributionRequest() === 'S') {
            if (!$this->getIbIban()) {
                $context->buildViolation('assurance_enterprise_import.ib_iban')
                    ->atPath('ibIban')
                    ->addViolation();
            } else {
                $validator = $context->getValidator();
                $errors = $validator->validate($this->getIbIban(), new Iban());
                if ($errors->count() > 0) {
                    $context->buildViolation($errors->get(0)->getMessage())
                        ->atPath('ibIban')
                        ->addViolation();
                }
            }
        }
        if ($this->getWorkplaceAddress() ||
            $this->getWorkplaceCity() ||
            $this->getWorkplacePostcode()) {
            if (!$this->getWorkplaceAddress()) {
                $context->buildViolation('assurance_enterprise_import.workplace_address_empty_or_null')
                    ->atPath('workplaceAddress')
                    ->addViolation();
            }
            if (!$this->getWorkplaceCity()) {
                $context->buildViolation('assurance_enterprise_import.workplace_city_empty_or_null')
                    ->atPath('workplaceCity')
                    ->addViolation();
            }
            if (!$this->getWorkplacePostcode()) {
                $context->buildViolation('assurance_enterprise_import.workplace_postcode_empty_or_null')
                    ->atPath('workplacePostcode')
                    ->addViolation();
            }
        }
    }

    public function getRow(): ?int
    {
        return $this->row;
    }

    public function setRow(?int $row): self
    {
        $this->row = $row;

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
     * @return FinancingImport|null
     */
    public function getFinancingImport(): ?FinancingImport
    {
        $financingImport = null;
        if ($this->getApplicationImport()) {
            $financingImport = $this->getApplicationImport()->getFinancingImports()->filter(function(FinancingImport $i){
                return $i->getPracticeId() === $this->getPracticeId();
            })->first() ?: null;
        }
        return $financingImport;
    }

    /**
     * @return LeasingImport|null
     */
    public function getLeasingImport(): ?LeasingImport
    {
        $leasingImport = null;
        if ($this->getApplicationImport()) {
            $leasingImport = $this->getApplicationImport()->getLeasingImports()->filter(function(LeasingImport $i){
                return $i->getPracticeId() === $this->getPracticeId();
            })->first() ?: null;
        }
        return $leasingImport;
    }
}
