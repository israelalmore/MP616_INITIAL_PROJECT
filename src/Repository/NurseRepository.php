<?php

namespace App\Repository;

use App\Entity\Nurse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Nurse>
 */
class NurseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Nurse::class);
    }

    /**
     * @return list<Nurse>
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.credentials', 'c')
            ->addSelect('c')
            ->orderBy('n.id', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByName(string $name): ?Nurse
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.credentials', 'c')
            ->addSelect('c')
            ->andWhere('LOWER(n.user) = LOWER(:name)')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Nurse>
     */
    public function searchByName(string $name): array
    {
        $exactMatches = $this->createQueryBuilder('n')
            ->leftJoin('n.credentials', 'c')
            ->addSelect('c')
            ->andWhere('LOWER(n.user) = LOWER(:name)')
            ->setParameter('name', $name)
            ->orderBy('n.id', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();

        if ($exactMatches !== []) {
            return $exactMatches;
        }

        return $this->createQueryBuilder('n')
            ->leftJoin('n.credentials', 'c')
            ->addSelect('c')
            ->andWhere('LOWER(n.user) LIKE LOWER(:name)')
            ->setParameter('name', '%'.$name.'%')
            ->orderBy('n.id', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function validateCredentials(string $user, string $password): bool
    {
        $nurse = $this->findOneBy(['user' => $user]);
        $storedPassword = $nurse?->getPassword();

        return $nurse?->getUser() === $user
            && $storedPassword !== null
            && hash_equals($storedPassword, $password);
    }
}
