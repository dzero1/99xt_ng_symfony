<?php

namespace App\Repository;

use App\Entity\BookCategories;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BookCategories|null find($id, $lockMode = null, $lockVersion = null)
 * @method BookCategories|null findOneBy(array $criteria, array $orderBy = null)
 * @method BookCategories[]    findAll()
 * @method BookCategories[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookCategoriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookCategories::class);
    }

    // /**
    //  * @return BookCategories[] Returns an array of BookCategories objects
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
    public function findOneBySomeField($value): ?BookCategories
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
