<?php

namespace App\Repository;

use App\Entity\AssuranceEnterpriseImport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AssuranceEnterpriseImport|null find($id, $lockMode = null, $lockVersion = null)
 * @method AssuranceEnterpriseImport|null findOneBy(array $criteria, array $orderBy = null)
 * @method AssuranceEnterpriseImport[]    findAll()
 * @method AssuranceEnterpriseImport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssuranceEnterpriseImportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssuranceEnterpriseImport::class);
    }

    // /**
    //  * @return AssuranceEnterpriseImport[] Returns an array of AssuranceEnterpriseImport objects
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
    public function findOneBySomeField($value): ?AssuranceEnterpriseImport
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
