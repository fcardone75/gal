<?php


namespace App\Service;


use App\Entity\Application;
use App\Entity\ApplicationGroup;
use App\Entity\ProtocolNumber;
use Doctrine\ORM\EntityManagerInterface;

class ApplicationGroupManager implements Contracts\ApplicationGroupManagerInterface
{
    /** @var EntityManagerInterface  */
    protected $entityManager;

    /** @var array  */
    protected $reservedProtocolNumbers = [];

    /** @var string  */
    protected $protocolNumberPrefix;

    /** @var array  */
    protected $protocolNumberPatterns = [
//        ApplicationGroup::class => '%s-%04d',
//        Application::class => '%s-%04d-%07d'
        ApplicationGroup::class => '%s_%08d',
        Application::class => '%s_%08d_%08d'
    ];

    public function __construct(
        EntityManagerInterface $entityManager,
        string $protocolNumberPrefix = '',
        array $protocolNumberPatterns = []
    ) {
        $this->entityManager = $entityManager;
        $this->protocolNumberPrefix = $protocolNumberPrefix;
    }

    public function reserveProtocolNumber(ApplicationGroup $entity, bool $andFlush = false): ProtocolNumber
    {
        $lastProtocolNumber = $this->entityManager->getRepository(ProtocolNumber::class)->findOneBy([], ['counter' => 'DESC']);
        $counter = $lastProtocolNumber ? $lastProtocolNumber->getCounter() : 0;

        $protocolNumber = new ProtocolNumber();

        $protocolNumber->setCounter(++$counter);

        $this->entityManager->persist($protocolNumber);

        if ($andFlush) {
            $this->entityManager->flush();
        }

        $this->reservedProtocolNumbers[ApplicationGroup::class][$entity->getId()] = $protocolNumber;

        return $protocolNumber;
    }

    public function hasReservedProtocolNumber(ApplicationGroup $applicationGroup): bool
    {
        return isset($this->reservedProtocolNumbers[ApplicationGroup::class][$applicationGroup->getId()]) &&
            $this->reservedProtocolNumbers[ApplicationGroup::class][$applicationGroup->getId()];
    }

    public function getReservedProtocolNumber(ApplicationGroup $applicationGroup): ProtocolNumber
    {
        if (!$this->hasReservedProtocolNumber($applicationGroup)) {
            throw new \LogicException(sprintf('Unable to find the reserved protocol number for entity with ID %s', $applicationGroup->getId()));
        }

        return $this->reservedProtocolNumbers[ApplicationGroup::class][$applicationGroup->getId()];
    }

    public function protocol(ApplicationGroup $applicationGroup)
    {
        if ($this->hasReservedProtocolNumber($applicationGroup)) {
            $protocolNumber = $this->getReservedProtocolNumber($applicationGroup);
            $protocolDate = new \DateTime();

            $applicationGroup
                ->setProtocolNumber(sprintf(
                    $this->protocolNumberPatterns[ApplicationGroup::class],
                    $this->protocolNumberPrefix,
                    $protocolNumber->getCounter()
                ))
                ->setProtocolDate($protocolDate)
                ->setStatus(ApplicationGroup::STATUS_REGISTERED);

            foreach ($applicationGroup->getApplications() as $idx => $application) {
                $application
                    ->setProtocolNumber(sprintf(
                        $this->protocolNumberPatterns[Application::class],
                        $this->protocolNumberPrefix,
                        $protocolNumber->getCounter(),
                        $idx + 1
                    ))
                    ->setProtocolDate($protocolDate)
                    ->setStatus(Application::STATUS_REGISTERED);
            }
        }
    }
}
