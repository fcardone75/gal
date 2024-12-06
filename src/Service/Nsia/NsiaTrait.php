<?php


namespace App\Service\Nsia;

use App\Entity\Application;
use App\Entity\Bank;
use App\Entity\BankLeasing;
use App\Entity\City;
use App\Entity\CompanySize;
use App\Entity\FinancialDestination;
use App\Entity\LeasingDestination;
use App\Entity\LegalForm;
use App\Entity\Periodicity;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

trait NsiaTrait
{

    protected $chars_to_replace = [
        "’" => "'",
    ];


    private function getApplicationBank(Application $application): ?Bank
    {
        return $this->entityManager->getRepository(Bank::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'name' => $application->getFDbfBank()]);
    }

    private function getApplicationBankLeasing(Application $application): ?BankLeasing
    {
        return $this->entityManager->getRepository(BankLeasing::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'name' => $application->getLSfBankLeasing()]);
    }

    private function getApplicationFinancialDestination(Application $application): ?FinancialDestination
    {
        return $this->entityManager->getRepository(FinancialDestination::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'destination' => $application->getFFinancialDestination()]);
    }

    private function getApplicationLeasingDestination(Application $application): ?LeasingDestination
    {
        return $this->entityManager->getRepository(LeasingDestination::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'destination' => $application->getLSfLeasingDestination()]);
    }

    private function getApplicationPeriodicityF(Application $application): ?Periodicity
    {
        return $this->entityManager->getRepository(Periodicity::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'type' => $application->getFDfPeriodicity()]);
    }

    private function getApplicationPeriodicityL(Application $application): ?Periodicity
    {
        return $this->entityManager->getRepository(Periodicity::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'type' => $application->getLDclPeriodicity()]);
    }

    private function getApplicationLegalForm(Application $application): ?LegalForm
    {
        return $this->entityManager->getRepository(LegalForm::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'name' => $application->getAeIbLegalForm()]);
    }

    private function getApplicationCompanySize(Application $application): ?CompanySize
    {
        return $this->entityManager->getRepository(CompanySize::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'size' => $application->getAeIbSize()]);
    }

    private function getCityByApplicationAndName(Application $application, $name = null): ?City
    {
        return $this->entityManager->getRepository(City::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'name' => $name]);
    }



    private function getApplicationBankByCode(Application $application, string $code = null): ?Bank
    {
        return $this->entityManager->getRepository(Bank::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'code' => $code]);
    }

    private function getApplicationBankLeasingByCode(Application $application, string $code = null): ?BankLeasing
    {
        return $this->entityManager->getRepository(BankLeasing::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'code' => $code]);
    }

    private function getApplicationFinancialDestinationByCode(Application $application, string $code = null): ?FinancialDestination
    {
        return $this->entityManager->getRepository(FinancialDestination::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'code' => $code]);
    }

    private function getApplicationLeasingDestinationByCode(Application $application, string $code = null): ?LeasingDestination
    {
        return $this->entityManager->getRepository(LeasingDestination::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'code' => $code]);
    }

    private function getApplicationPeriodicityByMonths(Application $application, string $months = null): ?Periodicity
    {
        return $this->entityManager->getRepository(Periodicity::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'months' => $months]);
    }

    private function getApplicationLegalFormByReferenceId(Application $application, string $referenceId = null): ?LegalForm
    {
        return $this->entityManager->getRepository(LegalForm::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'referenceId' => $referenceId]);
    }



    private function getFinancingProvisioningCertificationPeriodicity(Application $application): ?Periodicity
    {
//        return $this->entityManager->getRepository(Periodicity::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'type' => $application->getFDfPeriodicity()]);
        return $this->entityManager->getRepository(Periodicity::class)->findOneBy(['template' => $application->getApplicationImport()->getTemplate()->getId(), 'type' => $application->getFinancingProvisioningCertification()->getPeriodicity()]);
    }



    private function checkFieldString($value, $num_char = null)
    {
        $result = null;
        // check value max length
        $value = trim($value);
        if ($num_char !== null) {
            $value = substr ($value, 0, $num_char);
        }
        $value = strtoupper($value);

        // replace bad chars
        foreach($this->chars_to_replace as $search => $replace) {
            $value = str_replace ($search, $replace, $value);
        }
        if ($value) {
            $result = [];
            $result['_cdata'] = $value;
        }
        return $result;
    }

    private function checkFieldInteger($value, $num_char = null): ?int
    {
        // check value max length
        $value = trim($value);
//TODO: verificare se serve
//		if ($num_char !== null) {
//			$value = substr($value, 0, $num_char);
//		}
        $value = intval($value);
        return $value;
    }

    private function checkFieldDecimal($value, $num_char = null): ?string
    {
        // check value max length
        $value = trim($value);
//        if ($num_char !== null) {
//            $value = substr($value, 0, $num_char);
//        }
//TODO: in caso di 0 non deve essere inviato
        if ($value) {
            $value = number_format ($value , 2, '.', '');
        }
//TODO: verificare se serve
//        if ($num_char !== null) {
//            $value = substr($value, 0, $num_char);
//        }

        return $value;
    }

    private function checkFieldDate(\DateTimeInterface $dateTime = null): ?string
    {
        return $dateTime ? $dateTime->format('Y-m-d') : null;
    }



    private function formatFieldNumber($value, $num_char = null)
    {
        // pad left with 0
        $pad_string = 0;
        $pad_type = STR_PAD_LEFT;
//print_r((string) $value);
        $value = trim($value);
        // check value max length
        $value = substr ($value, 0, $num_char);
        return str_pad($value, $num_char, $pad_string, $pad_type);
    }



    private function notifyError($message = null): NotAcceptableHttpException
    {
        $this->mailer->sendNsiaErrorMessageNotification($message);
        throw new NotAcceptableHttpException($message);
    }

}
