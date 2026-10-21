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
            ->orderBy('n.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUsername(string $username): ?Nurse
    {
        return $this->createQueryBuilder('n')
            ->andWhere('LOWER(n.username) = LOWER(:username)')
            ->setParameter('username', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function validateCredentials(string $username, string $password): bool
    {
        $nurse = $this->findByUsername($username);
        $storedPassword = $nurse?->getPassword();

        return $nurse !== null
            && $nurse->getUsername() === $username
            && $storedPassword !== null
            && hash_equals($storedPassword, $password);
    }
}
