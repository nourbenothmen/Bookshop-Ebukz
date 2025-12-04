<?php

namespace App\Repository;
use App\Entity\User;
use App\Entity\Emprunt;
use App\Entity\Livre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Emprunt>
 */
class EmpruntRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Emprunt::class);
    }

    //    /**
    //     * @return Emprunt[] Returns an array of Emprunt objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Emprunt
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


    // src/Repository/EmpruntRepository.php

// src/Repository/EmpruntRepository.php

public function existsAnyEmpruntEnCoursForUser(User $user): bool
{
    return (bool) $this->createQueryBuilder('e')
        ->select('COUNT(e.id)')
        ->where('e.emprunteur = :user')
        ->andWhere('e.statut = :statut')
        ->setParameter('user', $user)
        ->setParameter('statut', 'en_cours')
        ->getQuery()
        ->getSingleScalarResult();
}
}
