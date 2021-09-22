<?php

namespace App\Repository;

use App\Entity\Discount;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Discount|null find($id, $lockMode = null, $lockVersion = null)
 * @method Discount|null findOneBy(array $criteria, array $orderBy = null)
 * @method Discount[]    findAll()
 * @method Discount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Discount::class);
    }

    /**
     * @return Discount[] Returns an array of Discount objects
     */
    public function findByBook($book)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.relateId = :id')
            ->setParameter('id', $book->getId())
            ->andWhere('d.relate_type = :type')
            ->setParameter('type', 'BOOK')
            ->andWhere('d.expire > :date')
            ->setParameter('date', new DateTimeImmutable())
            ->orderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Discount[] Returns an array of Discount objects
     */
    public function findCategoryWiseDiscounts()
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.relateId IS NOT NULL')
            ->andWhere('d.relateId != :id')
            ->setParameter('id', -1)
            ->andWhere('d.expire > :date')
            ->setParameter('date', new DateTimeImmutable())
            ->orderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Discount[] Returns an array of Discount objects
     */
    public function findAllCategoryDiscounts()
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.relateId = :id')
            ->andWhere('d.expire > :date')
            ->setParameter('id', -1)
            ->setParameter('date', new DateTimeImmutable())
            ->orderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Discount[] Returns an array of Discount objects
     */
    public function findSiteWideDiscounts()
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.relateId IS NULL')
            ->andWhere('d.expire > :date')
            ->setParameter('date', new DateTimeImmutable())
            ->orderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }


    // /**
    //  * @return Discount[] Returns an array of Discount objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Discount
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
