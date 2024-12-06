<?php

namespace App\Repository;

use App\Entity\Application;
use App\Entity\FinancingProvisioningCertification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Application|null find($id, $lockMode = null, $lockVersion = null)
 * @method Application|null findOneBy(array $criteria, array $orderBy = null)
 * @method Application[]    findAll()
 * @method Application[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    /**
     * @return Application[] Returns an array of Application objects
     */
    public function findAllForNsia(array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
//        return $this->createQueryBuilder('a')
////            ->join('ag.confidi', 'c')
//            ->join('a.applicationGroup', 'ag')
//            ->leftJoin('a.additionalContributions', 'ac')
////TODO: verificare controlli
//            ->andWhere('ag.protocolNumber IS NOT NULL')
//            ->andWhere('a.registryFileAudit IS NULL OR (ac.id IS NOT NULL AND ac.registryFileAudit IS NULL AND (ac.inImport = 0 OR ac.inImport IS NULL))')
//            ->andWhere('ag.id = :applicationGroup')
//            ->setParameter('applicationGroup', !empty($criteria['applicationGroup']) ? $criteria['applicationGroup'] : 'nd')
//            ->getQuery()
//            ->getResult()
//            ;

        $qb = $this->createQueryBuilder('a');

        $qb->join('a.applicationGroup', 'ag');
        $qb->leftJoin('a.additionalContributions', 'ac');
        $qb->leftJoin('a.financingProvisioningCertification', 'fpc');

        $qb->andWhere('ag.protocolNumber IS NOT NULL');
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

        $qb->andWhere('ag.id = :applicationGroup');
        $qb->setParameter('applicationGroup', !empty($criteria['applicationGroup']) ? $criteria['applicationGroup'] : 'nd');

        return $qb->getQuery()->getResult();
    }


    /**
     * @return Application[] Returns an array of Application objects
     */
    public function findAllForDashboard(array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
        $qb = $this->createQueryBuilder('a')
//            ->join('a.applicationGroup', 'ag')
//            ->join('ag.confidi', 'c')
//            ->join('a.applicationGroup', 'ag')
//            ->leftJoin('a.additionalContributions', 'ac')
//TODO: verificare controlli
            ;

        if (isset($criteria['confidi'])) {
            $qb->andWhere(
                $qb->expr()->eq('a.confidi', ':confidi')
            )->setParameter('confidi', $criteria['confidi']);
        }

//        if (isset($criteria['confidi'])) {
//            $query->join('a.applicationGroup', 'ag')
//                ->join('ag.confidi', 'c')
//                ->andWhere('c.id = :confidi')
//                ->setParameter('confidi', !empty($criteria['confidi']) ? $criteria['confidi'] : 'nd');
//        }
//
//        if (isset($criteria['contribution_type'])) {
//            $query->leftJoin('a.additionalContributions', 'ac');
//
//            if ($criteria['contribution_type'] === null) {
//                $query->andWhere('ac.id IS NULL');
//            } else {
//                $query->andWhere('ac.type = :contribution_type')
//                ->setParameter('contribution_type', !empty($criteria['contribution_type']) ? $criteria['contribution_type'] : 'nd');
//            }
//        }

        return $qb
            ->getQuery()
            ->getResult();
    }


    // /**
    //  * @return Application[] Returns an array of Application objects
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
    public function findOneBySomeField($value): ?Application
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
