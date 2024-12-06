<?php

namespace App\Repository;

use App\Entity\AdditionalContribution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AdditionalContribution|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdditionalContribution|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdditionalContribution[]    findAll()
 * @method AdditionalContribution[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdditionalContributionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdditionalContribution::class);
    }

    /**
     * @return AdditionalContribution[] Returns an array of AdditionalContribution objects
     */
    public function findAllForNsia(array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
//        return $this->createQueryBuilder('ac')
//            ->join('ac.application', 'a')
////TODO: verificare controlli
//            ->andWhere('ac.registryFileAudit IS NULL AND (ac.inImport IS NULL OR ac.inImport = 0)')
////            ->andWhere('ac.inImport IS NULL OR ac.inImport = 0')
////            ac.registryFileAudit IS NULL
//            ->andWhere('a.id = :application')
//            ->setParameter('application', !empty($criteria['application']) ? $criteria['application'] : 'nd')
//            ->getQuery()
//            ->getResult()
//            ;

//TODO: verificare ...
        $qb = $this->createQueryBuilder('ac');

        $qb->join('ac.application', 'a');
//        $qb->leftJoin('a.additionalContributions', 'ac');
//        $qb->leftJoin('a.financingProvisioningCertification', 'fpc');

//        $qb->andWhere('ag.protocolNumber IS NOT NULL');

        $qb->andWhere(
            $qb->expr()->andX(
//                $qb->expr()->orX(
                    $qb->expr()->isNull('ac.registryFileAudit'),
                    $qb->expr()->orX(
//                        $qb->expr()->isNotNull('ac.id'),
//                        $qb->expr()->isNull('ac.registryFileAudit'),
//                        $qb->expr()->orX(
                            $qb->expr()->eq('ac.inImport', 0),
                            $qb->expr()->isNull('ac.inImport')
//                        )
                    )
//                ),
//                $qb->expr()->orX(
//                    $qb->expr()->isNull('a.financingProvisioningCertification'),
//                    $qb->expr()->andX(
//                        $qb->expr()->isNotNull('fpc.id'),
//                        $qb->expr()->isNull('fpc.registryFileAudit'),
//                        $qb->expr()->eq('fpc.status', FinancingProvisioningCertification::STATUS_COMPLETED)
//                    )
//                )
            )
        );

        $qb->andWhere('a.id = :application');
        $qb->setParameter('application', !empty($criteria['application']) ? $criteria['application'] : 'nd');

        return $qb->getQuery()->getResult();
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
