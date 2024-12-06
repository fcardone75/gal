<?php

namespace App\Repository;

use App\Entity\ApplicationGroup;
use App\Entity\FinancingProvisioningCertification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ApplicationGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApplicationGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApplicationGroup[]    findAll()
 * @method ApplicationGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApplicationGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicationGroup::class);
    }

    /**
     * @return ApplicationGroup[] Returns an array of ApplicationGroup objects
     */
    public function findAllForNsia(array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
//        return $this->createQueryBuilder('ag')
//            ->join('ag.confidi', 'c')
//            ->join('ag.applications', 'a')
//            ->leftJoin('a.additionalContributions', 'ac')
////TODO: verificare controlli
//            ->andWhere('ag.protocolNumber IS NOT NULL')
//            ->andWhere('a.registryFileAudit IS NULL OR (ac.id IS NOT NULL AND ac.registryFileAudit IS NULL AND (ac.inImport = 0 OR ac.inImport IS NULL))')
//            ->andWhere('c.id = :confidi')
//            ->setParameter('confidi', !empty($criteria['confidi']) ? $criteria['confidi'] : 'nd')
//            ->getQuery()
//            ->getResult()
//            ;

        $qb = $this->createQueryBuilder('ag');

        $qb->join('ag.confidi', 'c');
        $qb->join('ag.applications', 'a');
        $qb->leftJoin('a.additionalContributions', 'ac');
        $qb->leftJoin('a.financingProvisioningCertification', 'fpc');

        $qb->andWhere('ag.protocolNumber IS NOT NULL');

        $qb->andWhere('c.id = :confidi');
        $qb->setParameter('confidi', !empty($criteria['confidi']) ? $criteria['confidi'] : 'nd');

/*
        $qb->andWhere(
            $qb->expr()->andX(
                $qb->expr()->orX(
                    $qb->expr()->isNull('a.registryFileAudit'),
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull('ac.id'),
                        $qb->expr()->isNull('ac.registryFileAudit'),
                        $qb->expr()->orX(
                            $qb->expr()->eq('ac.inImport', 0),
                            $qb->expr()->isNull('ac.inImport')
                        )
                    )
                ),
                $qb->expr()->orX(
//                    $qb->expr()->isNull('a.financingProvisioningCertification'),
                    $qb->expr()->isNull('fpc'),
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull('fpc.id'),
                        $qb->expr()->isNull('fpc.registryFileAudit'),
//                        $qb->expr()->eq('fpc.status', FinancingProvisioningCertification::STATUS_COMPLETED)
                        $qb->expr()->eq('fpc.status', ':status')
                    )
                )
            )
        );
        $qb->setParameter('status', FinancingProvisioningCertification::STATUS_COMPLETED);
*/

        $qb->andWhere(
//            $qb->expr()->andX(
            $qb->expr()->orX(
                $qb->expr()->isNull('a.registryFileAudit'),
                $qb->expr()->andX(
//                        $qb->expr()->isNotNull('ac.id'),
                    $qb->expr()->isNotNull('ac'),
                    $qb->expr()->isNull('ac.registryFileAudit'),
                    $qb->expr()->orX(
                        $qb->expr()->eq('ac.inImport', 0),
                        $qb->expr()->isNull('ac.inImport')
                    )
                ),
                $qb->expr()->andX(
//                        $qb->expr()->isNotNull('fpc.id'),
                    $qb->expr()->isNotNull('fpc'),
                    $qb->expr()->isNull('fpc.registryFileAudit'),
                    $qb->expr()->eq('fpc.status', ':status')
                )
            )
//            )
        );
        $qb->setParameter('status', FinancingProvisioningCertification::STATUS_COMPLETED);


        return $qb->getQuery()->getResult();
    }


    /**
     * @return ApplicationGroup[] Returns an array of ApplicationGroup objects
     */
    public function findConfidiProtocolPending(array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->createQueryBuilder('ag')
            ->join('ag.confidi', 'c')

//TODO: verificare controlli
            ->andWhere('ag.protocolNumber IS NOT NULL')
            ->andWhere('ag.status = :status')
            ->setParameter('status', !empty($criteria['status']) ? $criteria['status'] : 'nd')
            ->andWhere('c.id = :confidi')
            ->setParameter('confidi', !empty($criteria['confidi']) ? $criteria['confidi'] : 'nd')
            ->getQuery()
            ->getResult()
            ;
    }


    // /**
    //  * @return ApplicationGroup[] Returns an array of ApplicationGroup objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ApplicationGroup
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
