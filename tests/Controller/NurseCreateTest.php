<?php

namespace App\Tests\Controller;

use App\Entity\Nurse;
use App\Repository\NurseRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * POST /nurse against a real database. Each test runs inside a transaction
 * that is rolled back in tearDown, so no data is left behind.
 */
final class NurseCreateTest extends WebTestCase
{
    private const LOCAL_DATABASE_HOSTS = ['127.0.0.1', 'localhost', 'database'];

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private int $initialNurseCount;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $connection = $this->entityManager->getConnection();
        $host = $connection->getParams()['host'] ?? '';
        if (!in_array($host, self::LOCAL_DATABASE_HOSTS, true)) {
            self::markTestSkipped(sprintf('Database tests only run against a local database, "%s" given', $host));
        }

        $connection->beginTransaction();
        $this->initialNurseCount = $this->countNurses();
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager) && $this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->getConnection()->rollBack();
        }

        parent::tearDown();
    }

    public function testCreateNurseReturnsCreatedAndPersistsIt(): void
    {
        $this->client->jsonRequest('POST', '/nurse', [
            'name' => 'Laura Martinez',
            'username' => 'nurse2001',
            'password' => 'n2001pass',
            'email' => 'laura.martinez@example.com',
        ]);

        self::assertResponseStatusCodeSame(201);

        $response = $this->responseData();
        self::assertIsInt($response['id']);
        self::assertSame('Laura Martinez', $response['name']);
        self::assertSame('nurse2001', $response['username']);
        self::assertSame('laura.martinez@example.com', $response['email']);
        self::assertSame('active', $response['status']);
        self::assertNull($response['profileImage']);
        self::assertNotNull($response['createdAt']);
        self::assertNotNull($response['updatedAt']);
        self::assertArrayNotHasKey('password', $response);

        $this->entityManager->clear();
        $nurse = $this->entityManager->find(Nurse::class, $response['id']);
        self::assertInstanceOf(Nurse::class, $nurse);
        self::assertSame('nurse2001', $nurse->getUsername());
        self::assertSame('n2001pass', $nurse->getPassword());
    }

    public function testCreateNurseWithOptionalFieldsReturnsCreated(): void
    {
        $this->client->jsonRequest('POST', '/nurse', [
            'name' => 'Laura Martinez',
            'username' => 'nurse2001',
            'password' => 'n2001pass',
            'email' => 'laura.martinez@example.com',
            'profileImage' => 'https://example.com/laura.png',
            'status' => 'inactive',
        ]);

        self::assertResponseStatusCodeSame(201);

        $response = $this->responseData();
        self::assertSame('https://example.com/laura.png', $response['profileImage']);
        self::assertSame('inactive', $response['status']);
    }

    public function testCreateNurseTrimsNameUsernameAndEmail(): void
    {
        $this->client->jsonRequest('POST', '/nurse', [
            'name' => '  Laura Martinez  ',
            'username' => '  nurse2001  ',
            'password' => 'n2001pass',
            'email' => '  laura.martinez@example.com  ',
        ]);

        self::assertResponseStatusCodeSame(201);

        $response = $this->responseData();
        self::assertSame('Laura Martinez', $response['name']);
        self::assertSame('nurse2001', $response['username']);
        self::assertSame('laura.martinez@example.com', $response['email']);
    }

    public function testCreateNurseWithMissingFieldsReturnsBadRequest(): void
    {
        $this->client->jsonRequest('POST', '/nurse', [
            'name' => 'Laura Martinez',
        ]);

        self::assertResponseStatusCodeSame(400);

        $response = $this->responseData();
        self::assertFalse($response['success']);
        self::assertSame('Datos de la enfermera inválidos', $response['message']);
        self::assertSame(['username', 'password', 'email'], array_keys($response['errors']));
        self::assertSame($this->initialNurseCount, $this->countNurses());
    }

    public function testCreateNurseWithInvalidEmailReturnsBadRequest(): void
    {
        $this->client->jsonRequest('POST', '/nurse', [
            'name' => 'Laura Martinez',
            'username' => 'nurse2001',
            'password' => 'n2001pass',
            'email' => 'no-es-un-email',
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['email'], array_keys($this->responseData()['errors']));
        self::assertSame($this->initialNurseCount, $this->countNurses());
    }

    public function testCreateNurseWithBlankUsernameReturnsBadRequest(): void
    {
        $this->client->jsonRequest('POST', '/nurse', [
            'name' => 'Laura Martinez',
            'username' => '   ',
            'password' => 'n2001pass',
            'email' => 'laura.martinez@example.com',
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['username'], array_keys($this->responseData()['errors']));
    }

    public function testCreateNurseWithTooLongUsernameReturnsBadRequest(): void
    {
        $this->client->jsonRequest('POST', '/nurse', [
            'name' => 'Laura Martinez',
            'username' => str_repeat('a', 65),
            'password' => 'n2001pass',
            'email' => 'laura.martinez@example.com',
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['username'], array_keys($this->responseData()['errors']));
    }

    public function testCreateNurseWithNonStringNameReturnsBadRequest(): void
    {
        $this->client->jsonRequest('POST', '/nurse', [
            'name' => 123,
            'username' => 'nurse2001',
            'password' => 'n2001pass',
            'email' => 'laura.martinez@example.com',
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['name'], array_keys($this->responseData()['errors']));
    }

    public function testCreateNurseWithUnknownFieldReturnsBadRequest(): void
    {
        $this->client->jsonRequest('POST', '/nurse', [
            'name' => 'Laura Martinez',
            'username' => 'nurse2001',
            'password' => 'n2001pass',
            'email' => 'laura.martinez@example.com',
            'role' => 'admin',
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['role'], array_keys($this->responseData()['errors']));
    }

    public function testCreateNurseWithInvalidJsonReturnsBadRequest(): void
    {
        $this->client->request('POST', '/nurse', [], [], ['CONTENT_TYPE' => 'application/json'], '{"name":');

        self::assertResponseStatusCodeSame(400);
        self::assertSame('El cuerpo de la petición debe ser un JSON válido', $this->responseData()['message']);
    }

    public function testCreateNurseWithExistingUsernameReturnsBadRequest(): void
    {
        $this->persistNurse('nurse1', 'ana.garcia@example.com');

        $this->client->jsonRequest('POST', '/nurse', [
            'name' => 'Ana Garcia',
            'username' => 'nurse1',
            'password' => 'n1pass',
            'email' => 'otra@example.com',
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['username' => 'El username ya existe'], $this->responseData()['errors']);
        self::assertSame($this->initialNurseCount + 1, $this->countNurses());
    }

    public function testCreateNurseWithExistingUsernameInDifferentCaseReturnsBadRequest(): void
    {
        $this->persistNurse('nurse1', 'ana.garcia@example.com');

        $this->client->jsonRequest('POST', '/nurse', [
            'name' => 'Ana Garcia',
            'username' => 'NURSE1',
            'password' => 'n1pass',
            'email' => 'otra@example.com',
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['username' => 'El username ya existe'], $this->responseData()['errors']);
    }

    public function testCreateNurseWithExistingEmailReturnsBadRequest(): void
    {
        $this->persistNurse('nurse1', 'ana.garcia@example.com');

        $this->client->jsonRequest('POST', '/nurse', [
            'name' => 'Ana Garcia',
            'username' => 'nurse2',
            'password' => 'n2pass',
            'email' => 'ana.garcia@example.com',
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['email' => 'El email ya existe'], $this->responseData()['errors']);
        self::assertSame($this->initialNurseCount + 1, $this->countNurses());
    }

    public function testCreateNurseReturnsInternalServerErrorWhenDatabaseFails(): void
    {
        $nurseRepository = $this->createMock(NurseRepository::class);
        $nurseRepository->method('findByUsername')->willReturn(null);
        $nurseRepository->method('findOneBy')->willReturn(null);
        $nurseRepository->method('save')->willThrowException(
            new class('Database unavailable') extends \RuntimeException implements DBALException {}
        );
        static::getContainer()->set(NurseRepository::class, $nurseRepository);

        $this->client->jsonRequest('POST', '/nurse', [
            'name' => 'Laura Martinez',
            'username' => 'nurse2001',
            'password' => 'n2001pass',
            'email' => 'laura.martinez@example.com',
        ]);

        self::assertResponseStatusCodeSame(500);
        self::assertJsonStringEqualsJsonString(
            '{"success":false,"message":"No se ha podido registrar la enfermera"}',
            $this->client->getResponse()->getContent()
        );
    }

    private function persistNurse(string $username, string $email): void
    {
        $nurse = (new Nurse())
            ->setName('Ana Garcia')
            ->setUsername($username)
            ->setPassword('secret')
            ->setEmail($email)
            ->setStatus('active');

        $this->entityManager->persist($nurse);
        $this->entityManager->flush();
    }

    private function countNurses(): int
    {
        return static::getContainer()->get(NurseRepository::class)->count([]);
    }

    /**
     * @return array<string, mixed>
     */
    private function responseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
