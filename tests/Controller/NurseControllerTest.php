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

        $client->request('GET', '/nurse/name/ana');

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
        $client->request('GET', '/nurse/name/noexiste');

        self::assertResponseStatusCodeSame(404);
        self::assertJsonStringEqualsJsonString(
            '{"success":false,"message":"Enfermera no encontrada"}',
            $client->getResponse()->getContent()
        );
    }

    public function testFindByUsernameWithBlankUsernameReturnsBadRequest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/nurse/name/%20');

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

    public function testCreateNurseReturnsCreated(): void
    {
        $client = static::createClient();
        $nurseRepository = $this->createMock(NurseRepository::class);
        $nurseRepository->method('findByUsername')->willReturn(null);
        $nurseRepository->method('findOneBy')->willReturn(null);
        $nurseRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(fn(Nurse $nurse): bool => $nurse->getUsername() === 'nurse2001'
                && $nurse->getName() === 'Laura Martinez'
                && $nurse->getEmail() === 'laura.martinez@example.com'
                && $nurse->getStatus() === 'active'));
        static::getContainer()->set(NurseRepository::class, $nurseRepository);

        $client->jsonRequest('POST', '/nurse', [
            'name' => 'Laura Martinez',
            'username' => 'nurse2001',
            'password' => 'n2001pass',
            'email' => 'laura.martinez@example.com',
        ]);

        self::assertResponseStatusCodeSame(201);

        $nurse = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('nurse2001', $nurse['username']);
        self::assertSame('active', $nurse['status']);
        self::assertArrayNotHasKey('password', $nurse);
    }

    public function testCreateNurseWithMissingFieldsReturnsBadRequest(): void
    {
        $client = static::createClient();
        $nurseRepository = $this->createMock(NurseRepository::class);
        $nurseRepository->expects(self::never())->method('save');
        static::getContainer()->set(NurseRepository::class, $nurseRepository);

        $client->jsonRequest('POST', '/nurse', [
            'name' => 'Laura Martinez',
            'email' => 'no-es-un-email',
        ]);

        self::assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($response['success']);
        self::assertSame(['username', 'password', 'email'], array_keys($response['errors']));
    }

    public function testCreateNurseWithInvalidJsonReturnsBadRequest(): void
    {
        $client = static::createClient();
        $client->request('POST', '/nurse', [], [], ['CONTENT_TYPE' => 'application/json'], '{"name":');

        self::assertResponseStatusCodeSame(400);
    }

    public function testCreateNurseWithExistingUsernameReturnsBadRequest(): void
    {
        $client = static::createClient();
        $nurseRepository = $this->createMock(NurseRepository::class);
        $nurseRepository->method('findByUsername')->willReturn($this->createNurse('Ana Garcia', 'nurse1'));
        $nurseRepository->expects(self::never())->method('save');
        static::getContainer()->set(NurseRepository::class, $nurseRepository);

        $client->jsonRequest('POST', '/nurse', [
            'name' => 'Ana Garcia',
            'username' => 'nurse1',
            'password' => 'n1pass',
            'email' => 'otra@example.com',
        ]);

        self::assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('username', $response['errors']);
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
