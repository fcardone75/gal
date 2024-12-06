<?php


namespace App\Service;


use App\Entity\Application;
use App\Entity\ApplicationMessage;
use App\Entity\ApplicationStatusLog;
use App\Service\Contracts\ApplicationStatusManagerInterface;
use App\Service\Contracts\Security\RoleProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApplicationStatusManager implements ApplicationStatusManagerInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var RoleProviderInterface */
    private $roleProvider;

    public function __construct(
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        RoleProviderInterface $roleProvider
    ) {
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->roleProvider = $roleProvider;
    }

    public function assignStatusToApplication(
        string $status,
        Application $application,
        ?string $description = null,
        $translationParameters = []
    ):void
    {
        $application->setStatus($status);
        $statusLog = new ApplicationStatusLog();

        if (null === $description && $this->isManagedStatus($status)) {
            $description = $this->getDefaultDescriptionForStatus($status);
        }

        $statusLog
            ->setStatus($application->getStatus())
            ->setDescription($this->translator->trans($description, $translationParameters, 'application_status'));
        $application->addApplicationStatusLog($statusLog);
        $this->entityManager->persist($statusLog);
        if ($application->getId()) {
            $classMetadata = $this->entityManager->getClassMetadata(ApplicationStatusLog::class);
            $this->entityManager->getUnitOfWork()->computeChangeSet($classMetadata, $statusLog);
        }
    }

    public function assignInquestStatusToApplication(
        ApplicationMessage $applicationMessage
    ): void
    {
        if($applicationMessage->getPublished()) {
            $application = $applicationMessage->getApplication();
            $applicationMessageCreatedBy = $applicationMessage->getCreatedBy();

            if($this->roleProvider->userHasRoleArtigiancassa($applicationMessageCreatedBy)) {
                $application->setInquestStatus(Application::INQUEST_STATUS_INTEGRATION_REQUESTED);
            } else if ($this->roleProvider->userHasRoleConfidi($applicationMessageCreatedBy)) {
                $application->setInquestStatus(Application::INQUEST_STATUS_INTEGRATION_SUPPLIED);
            }

            $classMetadata = $this->entityManager->getClassMetadata(Application::class);
            $this->entityManager->getUnitOfWork()->computeChangeSet($classMetadata, $application);
        }
    }

    protected function getDefaultDescriptionForStatus(string $status): string
    {
        return 'default_description.' . $status;
    }

    protected function isManagedStatus(string $status): bool
    {
        return in_array($status, [
            Application::STATUS_CREATED,
            Application::STATUS_LINKED,
            Application::STATUS_REGISTERED
        ]);
    }

    public function getStatusAsOptionArray(): array
    {
        return [
            $this->translator->trans(Application::STATUS_CREATED, [], 'application_status') => Application::STATUS_CREATED,
            $this->translator->trans(Application::STATUS_LINKED, [], 'application_status') => Application::STATUS_LINKED,
            $this->translator->trans(Application::STATUS_REGISTERED, [], 'application_status') => Application::STATUS_REGISTERED,
            $this->translator->trans(Application::STATUS_NSIA_00100, [], 'application_status') => Application::STATUS_NSIA_00100,
            $this->translator->trans(Application::STATUS_NSIA_00101, [], 'application_status') => Application::STATUS_NSIA_00101,
            $this->translator->trans(Application::STATUS_NSIA_00102, [], 'application_status') => Application::STATUS_NSIA_00102,
            $this->translator->trans(Application::STATUS_NSIA_00103, [], 'application_status') => Application::STATUS_NSIA_00103,
            $this->translator->trans(Application::STATUS_NSIA_00104, [], 'application_status') => Application::STATUS_NSIA_00104,
$this->translator->trans(Application::STATUS_NSIA_00111, [], 'application_status') => Application::STATUS_NSIA_00111,
$this->translator->trans(Application::STATUS_NSIA_00110, [], 'application_status') => Application::STATUS_NSIA_00110,
            $this->translator->trans(Application::STATUS_NSIA_00105, [], 'application_status') => Application::STATUS_NSIA_00105,
            $this->translator->trans(Application::STATUS_NSIA_00106, [], 'application_status') => Application::STATUS_NSIA_00106,
            $this->translator->trans(Application::STATUS_NSIA_00107, [], 'application_status') => Application::STATUS_NSIA_00107,
            $this->translator->trans(Application::STATUS_NSIA_00108, [], 'application_status') => Application::STATUS_NSIA_00108,
            $this->translator->trans(Application::STATUS_NSIA_00109, [], 'application_status') => Application::STATUS_NSIA_00109,
            $this->translator->trans(Application::STATUS_NSIA_00200, [], 'application_status') => Application::STATUS_NSIA_00200,
            $this->translator->trans(Application::STATUS_NSIA_00201, [], 'application_status') => Application::STATUS_NSIA_00201,
            $this->translator->trans(Application::STATUS_NSIA_00202, [], 'application_status') => Application::STATUS_NSIA_00202,
            $this->translator->trans(Application::STATUS_NSIA_00205, [], 'application_status') => Application::STATUS_NSIA_00205,
            $this->translator->trans(Application::STATUS_NSIA_00206, [], 'application_status') => Application::STATUS_NSIA_00206,
            $this->translator->trans(Application::STATUS_NSIA_00207, [], 'application_status') => Application::STATUS_NSIA_00207,
        ];
    }
}
