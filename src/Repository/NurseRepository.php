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

    public function findAll(): array
    {
        return $this->nurseData->findAll();
    }

    public function findByName(string $name): ?array
    {
        return $this->nurseData->findByName($name);
    }

    public function validateCredentials(string $user, string $password): bool
    {
        return $this->nurseData->authenticate($user, $password) !== null;
    }
}
