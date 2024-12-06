<?php


namespace App\Service\Contracts;


use App\Entity\Application;
use App\Entity\ApplicationMessage;

interface ApplicationStatusManagerInterface
{
    /**
     * @param string $status
     * @param Application $application
     * @param string|null $description
     * @param array $translationParameters
     */
    public function assignStatusToApplication(
        string $status,
        Application $application,
        ?string $description = null,
        $translationParameters = []
    ): void;

    /**
     * @param ApplicationMessage $applicationMessage
     */
    public function assignInquestStatusToApplication(
        ApplicationMessage $applicationMessage
    ): void;

    public function getStatusAsOptionArray(): array;
}
