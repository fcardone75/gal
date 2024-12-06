<?php


namespace App\Service\Contracts;

use App\Entity\ApplicationMessage;
use App\Entity\FinancingProvisioningCertification;
use App\Model\UserInterface;

use App\Entity\Application;

interface MailerInterface
{
    public function sendResetPasswordEmail(UserInterface $user);

    public function sendApplicationMessageNotification(ApplicationMessage $applicationMessage);

    public function sendUserCreatedEmail(UserInterface $user);

    public function sendNsiaErrorMessageNotification(string $message);

    public function sendFPCertificationNotification(FinancingProvisioningCertification $financingProvisioningCertification);
}
