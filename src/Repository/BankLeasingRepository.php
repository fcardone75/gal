<?php

namespace App\Repository;

use App\Entity\BankLeasing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BankLeasing|null find($id, $lockMode = null, $lockVersion = null)
 * @method BankLeasing|null findOneBy(array $criteria, array $orderBy = null)
 * @method BankLeasing[]    findAll()
 * @method BankLeasing[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BankLeasingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankLeasing::class);
    }

    // /**
    //  * @return BankLeasing[] Returns an array of BankLeasing objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?BankLeasing
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
