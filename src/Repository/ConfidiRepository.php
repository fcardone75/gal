<?php

namespace App\Repository;

use App\Entity\Confidi;
use App\Entity\FinancingProvisioningCertification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Confidi|null find($id, $lockMode = null, $lockVersion = null)
 * @method Confidi|null findOneBy(array $criteria, array $orderBy = null)
 * @method Confidi[]    findAll()
 * @method Confidi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfidiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Confidi::class);
    }

     /**
      * @return Confidi[] Returns an array of Confidi objects
      */
    public function findAllForNsia(array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
//        return $this->createQueryBuilder('c')
//            ->join('c.applicationGroups', 'ag')
//            ->join('ag.applications', 'a')
//            ->leftJoin('a.additionalContributions', 'ac')
////TODO: verificare controlli
//            ->andWhere('ag.protocolNumber IS NOT NULL')
//            ->andWhere('a.registryFileAudit IS NULL OR (ac.id IS NOT NULL AND ac.registryFileAudit IS NULL AND (ac.inImport = 0 OR ac.inImport IS NULL))')
//            ->getQuery()
//            ->getResult()
//        ;

        $qb = $this->createQueryBuilder('c');

        $qb->join('c.applicationGroups', 'ag');
        $qb->join('ag.applications', 'a');
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

        return $qb->getQuery()->getResult();

//        $qb->andWhere(
//            $qb->expr()->andX(
//                $qb->expr()->orX(
//                    $qb->expr()->isNull('a.registryFileAudit'),
//                    $qb->expr()->andX(
//                        $qb->expr()->orX(
//                            $qb->expr()->isNotNull('ac.id'),
//                            $qb->expr()->isNull('ac.registryFileAudit'),
//                            $qb->expr()->andX(
//                                $qb->expr()->orX(
//                                    $qb->expr()->eq('ac.inImport', 0)
//                                ),
//                                $qb->expr()->orX(
//                                    $qb->expr()->isNull('ac.inImport')
//                                )
//                            )
//                        )
//                    )
//                )
//            )
//        );

    }

    // /**
    //  * @return Confidi[] Returns an array of Confidi objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Confidi
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
