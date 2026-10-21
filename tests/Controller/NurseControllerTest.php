<?php

namespace App\Tests\Controller;

use App\Entity\Nurse;
use App\Repository\NurseRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class NurseControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/nurse');

        self::assertResponseIsSuccessful();
    }

    public function testGetAllReturnsNurseDetailsWithoutPasswords(): void
    {
        $client = static::createClient();
        $nurse = $this->createNurse('Ana Garcia', 'nurse1');
        $nurseRepository = $this->createMock(NurseRepository::class);
        $nurseRepository->method('findAll')->willReturn([$nurse]);
        static::getContainer()->set(NurseRepository::class, $nurseRepository);

        $client->request('GET', '/nurse/index');

        self::assertResponseIsSuccessful();

        $nurses = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([[
            'id' => null,
            'name' => 'Ana Garcia',
            'username' => 'nurse1',
            'createdAt' => '2026-10-20 09:00:00',
            'updatedAt' => '2026-10-20 09:00:00',
            'profileImage' => null,
            'email' => 'ana.garcia@example.com',
            'status' => 'active',
        ]], $nurses);
    }

    public function testFindByUsernameReturnsNurseWhenExists(): void
    {
        $client = static::createClient();
        $nurse = $this->createNurse('Ana Garcia', 'nurse1');
        $nurseRepository = $this->createMock(NurseRepository::class);
        $nurseRepository->method('findByUsername')->with('ana')->willReturn($nurse);
        static::getContainer()->set(NurseRepository::class, $nurseRepository);

        $client->request('GET', '/nurse/username/ana');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"id":null,"name":"Ana Garcia","username":"nurse1","createdAt":"2026-10-20 09:00:00","updatedAt":"2026-10-20 09:00:00","profileImage":null,"email":"ana.garcia@example.com","status":"active"}',
            $client->getResponse()->getContent()
        );
    }

    public function testFindByUsernameReturnsNotFoundWhenUsernameDoesNotExist(): void
    {
        $client = static::createClient();
        $nurseRepository = $this->createMock(NurseRepository::class);
        $nurseRepository->method('findByUsername')->willReturn(null);
        static::getContainer()->set(NurseRepository::class, $nurseRepository);
        $client->request('GET', '/nurse/username/noexiste');

        self::assertResponseStatusCodeSame(404);
        self::assertJsonStringEqualsJsonString(
            '{"success":false,"message":"Enfermera no encontrada"}',
            $client->getResponse()->getContent()
        );
    }

    public function testFindByUsernameWithBlankUsernameReturnsBadRequest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/nurse/username/%20');

        self::assertResponseStatusCodeSame(400);
    }

    public function testLoginWithValidCredentials(): void
    {
        $client = static::createClient();
        $nurseRepository = $this->createMock(NurseRepository::class);
        $nurseRepository->method('validateCredentials')->with('nurse1', 'n1pass')->willReturn(true);
        static::getContainer()->set(NurseRepository::class, $nurseRepository);
        $client->jsonRequest('POST', '/nurse/login', [
            'username' => 'nurse1',
            'password' => 'n1pass',
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"success":true,"message":"Credenciales correctas"}',
            $client->getResponse()->getContent()
        );
    }

    public function testLoginWithAnotherNurseAccount(): void
    {
        $client = static::createClient();
        $nurseRepository = $this->createMock(NurseRepository::class);
        $nurseRepository->method('validateCredentials')->with('nurse3', 'n3pass')->willReturn(true);
        static::getContainer()->set(NurseRepository::class, $nurseRepository);
        $client->jsonRequest('POST', '/nurse/login', [
            'username' => 'nurse3',
            'password' => 'n3pass',
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"success":true,"message":"Credenciales correctas"}',
            $client->getResponse()->getContent()
        );
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();
        $nurseRepository = $this->createMock(NurseRepository::class);
        $nurseRepository->method('validateCredentials')->willReturn(false);
        static::getContainer()->set(NurseRepository::class, $nurseRepository);
        $client->jsonRequest('POST', '/nurse/login', [
            'username' => 'nurse1',
            'password' => 'incorrecta',
        ]);

        self::assertResponseStatusCodeSame(401);
        self::assertJsonStringEqualsJsonString(
            '{"success":false,"message":"Credenciales incorrectas"}',
            $client->getResponse()->getContent()
        );
    }

    private function createNurse(string $name, string $username): Nurse
    {
        return (new Nurse())
            ->setName($name)
            ->setUsername($username)
            ->setPassword('secret')
            ->setCreatedAt(new \DateTimeImmutable('2026-10-20 09:00:00'))
            ->setUpdatedAt(new \DateTimeImmutable('2026-10-20 09:00:00'))
            ->setEmail('ana.garcia@example.com')
            ->setStatus('active');
    }
}
