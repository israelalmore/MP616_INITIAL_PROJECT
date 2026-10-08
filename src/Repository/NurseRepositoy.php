<?php

namespace App\Repository;

use App\Service\NurseJsonDataProvider;

final class NurseRepositoy
{
    public function __construct(private readonly NurseJsonDataProvider $nurseData) {}

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
