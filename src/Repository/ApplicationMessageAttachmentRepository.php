<?php

namespace App\Repository;

use App\Entity\ApplicationMessageAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ApplicationMessageAttachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApplicationMessageAttachment|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApplicationMessageAttachment[]    findAll()
 * @method ApplicationMessageAttachment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApplicationMessageAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicationMessageAttachment::class);
    }

    // /**
    //  * @return ApplicationMessageAttachment[] Returns an array of ApplicationMessageAttachment objects
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
    public function findOneBySomeField($value): ?ApplicationMessageAttachment
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
