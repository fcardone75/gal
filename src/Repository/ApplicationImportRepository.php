<?php

namespace App\Repository;

use App\Entity\ApplicationImport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ApplicationImport|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApplicationImport|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApplicationImport[]    findAll()
 * @method ApplicationImport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApplicationImportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicationImport::class);
    }

    // /**
    //  * @return ApplicationImport[] Returns an array of ApplicationImport objects
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
    public function findOneBySomeField($value): ?ApplicationImport
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
