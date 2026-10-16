<?php

namespace App\Tests\Controller;

use App\Entity\Nurse;
use App\Entity\NurseCredential;
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

    public function testGetAllReturnsNurseNamesAndCredentialLists(): void
    {
        $client = static::createClient();
        $client->request('GET', '/nurse/index');

        self::assertResponseIsSuccessful();

        $nurses = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(20, $nurses);
        foreach ($nurses as $index => $nurse) {
            self::assertSame('nurse'.($index + 1), $nurse['user']);
            self::assertArrayHasKey('credentials', $nurse);
            self::assertIsArray($nurse['credentials']);
            self::assertArrayNotHasKey('password', $nurse);
        }
    }

    public function testGetAllReturnsExpectedNurseDataWithoutPasswords(): void
    {
        $client = static::createClient();
        $nurse = (new Nurse())
            ->setUser('nurse1')
            ->setPassword('secret');
        $nurse->addCredential((new NurseCredential())
            ->setLicenseNumber('RN-12345')
            ->setCertification('Basic Life Support')
            ->setIssuingBody('American Red Cross')
            ->setIssueDate(new \DateTimeImmutable('2026-01-01'))
            ->setExpirationDate(new \DateTimeImmutable('2027-01-01')));

        $nurseRepository = $this->createMock(NurseRepository::class);
        $nurseRepository->method('findAll')->willReturn([$nurse]);
        static::getContainer()->set(NurseRepository::class, $nurseRepository);

        $client->request('GET', '/nurse/index');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '[{"user":"nurse1","credentials":[{"licenseNumber":"RN-12345","certification":"Basic Life Support","issuingBody":"American Red Cross","expirationDate":"2027-01-01"}]}]',
            $client->getResponse()->getContent()
        );
    }

    public function testGetAllReturnsEmptyJsonListWhenNoNursesAreRegistered(): void
    {
        $client = static::createClient();
        $nurseRepository = $this->createMock(NurseRepository::class);
        $nurseRepository->method('findAll')->willReturn([]);
        static::getContainer()->set(NurseRepository::class, $nurseRepository);

        $client->request('GET', '/nurse/index');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('[]', $client->getResponse()->getContent());
    }
  
    public function testFindByNameReturnsNurseWhenExists(): void
    {
        $client = static::createClient();
        $client->request('GET', '/nurse/name/nurse1');

        self::assertResponseIsSuccessful();
        $nurse = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('nurse1', $nurse['user']);
        self::assertSame([
            [
                'licenseNumber' => 'RN-12345',
                'certification' => 'Basic Life Support',
                'issuingBody' => 'American Red Cross',
                'expirationDate' => '2027-01-01',
            ],
        ], $nurse['credentials']);
        self::assertArrayNotHasKey('password', $nurse);
    }

    public function testFindByNameIsCaseInsensitive(): void
    {
        $client = static::createClient();
        $client->request('GET', '/nurse/name/NURSE2');

        self::assertResponseIsSuccessful();
        $nurse = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('nurse2', $nurse['user']);
        self::assertArrayHasKey('credentials', $nurse);
    }

    public function testFindByNameReturnsNotFoundWhenNameDoesNotExist(): void
    {
        $client = static::createClient();
        $client->request('GET', '/nurse/name/noexiste');

        self::assertResponseStatusCodeSame(404);
        self::assertJsonStringEqualsJsonString(
            '{"success":false,"message":"Enfermera no encontrada"}',
            $client->getResponse()->getContent()
        );
    }

    public function testFindByNameWithBlankNameReturnsBadRequest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/nurse/name/%20');

        self::assertResponseStatusCodeSame(400);
    }

    public function testLoginWithValidCredentials(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/login', [
            'user' => 'nurse1',
            'password' => 'n1pass',
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"success":true,"message":"Credenciales correctas"}',
            $client->getResponse()->getContent()
        );
    }

    public function testLoginWithNewNurseCredentials(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/login', [
            'user' => 'nurse3',
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
        $client->jsonRequest('POST', '/login', [
            'user' => 'nurse1',
            'password' => 'incorrecta',
        ]);

        self::assertResponseStatusCodeSame(401);
        self::assertJsonStringEqualsJsonString(
            '{"message":"Credenciales incorrectas"}',
            $client->getResponse()->getContent()
        );
    }
}
