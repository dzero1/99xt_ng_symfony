<?php

namespace App\Repository;

use App\Entity\CartDiscounts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CartDiscounts|null find($id, $lockMode = null, $lockVersion = null)
 * @method CartDiscounts|null findOneBy(array $criteria, array $orderBy = null)
 * @method CartDiscounts[]    findAll()
 * @method CartDiscounts[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartDiscountsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartDiscounts::class);
    }

    // /**
    //  * @return CartDiscounts[] Returns an array of CartDiscounts objects
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
    public function findOneBySomeField($value): ?CartDiscounts
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
