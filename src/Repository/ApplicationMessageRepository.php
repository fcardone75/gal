<?php

namespace App\Repository;

use App\Entity\ApplicationMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ApplicationMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApplicationMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApplicationMessage[]    findAll()
 * @method ApplicationMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApplicationMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicationMessage::class);
    }

    // /**
    //  * @return ApplicationMessage[] Returns an array of ApplicationMessage objects
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
    public function findOneBySomeField($value): ?ApplicationMessage
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
