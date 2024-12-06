<?php


namespace App\Service\Contracts;


use App\Entity\ApplicationGroup;
use App\Entity\ProtocolNumber;

interface ApplicationGroupManagerInterface
{
    /**
     * @param ApplicationGroup $applicationGroup
     * @param bool $andFlush
     * @return ProtocolNumber
     */
    public function reserveProtocolNumber(ApplicationGroup $applicationGroup, bool $andFlush = false): ProtocolNumber;

    /**
     * @param ApplicationGroup $applicationGroup
     * @return bool
     */
    public function hasReservedProtocolNumber(ApplicationGroup $applicationGroup): bool;

    /**
     * @param ApplicationGroup $applicationGroup
     * @return ProtocolNumber
     * @throws \LogicException
     */
    public function getReservedProtocolNumber(ApplicationGroup $applicationGroup): ProtocolNumber;

    /**
     * @param ApplicationGroup $applicationGroup
     * @return mixed
     */
    public function protocol(ApplicationGroup $applicationGroup);
}
