<?php

namespace App\Tests\Repository;

use App\Entity\Nurse;
use App\Repository\NurseRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class NurseRepositoryTest extends TestCase
{
    public function testValidateCredentialsComparesPlainTextPassword(): void
    {
        $nurse = (new Nurse())
            ->setUsername('nurse1')
            ->setPassword('n1pass');
        $repository = new class(
            $this->createMock(ManagerRegistry::class),
            $nurse,
        ) extends NurseRepository {
            public function __construct(
                ManagerRegistry $registry,
                private readonly Nurse $nurse,
            ) {
                parent::__construct($registry);
            }

            public function findByUsername(string $username): ?Nurse
            {
                return $this->nurse;
            }
        };

        self::assertTrue($repository->validateCredentials('nurse1', 'n1pass'));
        self::assertFalse($repository->validateCredentials('nurse1', 'wrong'));
        self::assertFalse($repository->validateCredentials('Nurse1', 'n1pass'));
    }
}
