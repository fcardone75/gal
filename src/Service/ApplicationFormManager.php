<?php

namespace App\Service;

use App\Entity\AdditionalContribution;
use App\Entity\Application;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ApplicationFormManager
{
    const APPLICATION_MESSAGE_FORM = 'applicationMessage';
    const APPLICATION_ADDITIONAL_CONTRIBUTIONS_FORM = 'applicationAdditionalContributions';

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function canEditForm(Application $application, $form = ''): bool
    {
		switch ($form) {
            case self::APPLICATION_MESSAGE_FORM:
				$result =
                    $this->authorizationChecker->isGranted("ROLE_OPERATORE_ARTIGIANCASSA") ||
                    (
                        $this->authorizationChecker->isGranted("ROLE_OPERATORE_CONFIDI") &&
                        $application->getInquestStatus() !== Application::INQUEST_STATUS_CLOSED
                    )
                ;
				break;
            case self::APPLICATION_ADDITIONAL_CONTRIBUTIONS_FORM:
// TODO
// http://mantis.synesthesia.it/view.php?id=9116
                if (!$application->getNsiaNumeroPosizione()) {
                    return false;
                }

//TODO: questo controllo solo per pratiche con finanziamento non erogato (NO)
//                if (!$application->getStatus() != Application::STATUS_NSIA_00110) {
//                if ($application->getStatus() != Application::STATUS_NSIA_00110) {
                if ($application->getFDfLoanProvidedAtImport() == 'N' && $application->getStatus() != Application::STATUS_NSIA_00110) {
                    return false;
                }

                $additionalContributionsExisting = $application->getExistingAdditionalContributions();
                $result =
                    $additionalContributionsExisting->count() < 2 ||
                    (
                        $additionalContributionsExisting->count() < 3 &&
                        !$additionalContributionsExisting->exists(function($key, AdditionalContribution $additionalContribution) {
                            return $additionalContribution->getType() === AdditionalContribution::TYPE_ABB;
                        })
                    )
                ;
                break;
            default:
                $result = false;
		}
		return $result;
    }
}
