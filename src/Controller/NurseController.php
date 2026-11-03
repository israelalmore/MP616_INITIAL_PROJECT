<?php

namespace App\Controller;

use App\Entity\Nurse;
use App\Repository\NurseRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/nurse')]
final class NurseController extends AbstractController
{
    private const DEFAULT_STATUS = 'active';

    #[Route('', name: 'app_nurse_controller', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => "Welcome to the new Controller",
            'path' => "src/Controller/NurseController.php",
        ]);
    }

    #[Route('', name: 'app_nurse_create', methods: ['POST'])]
    public function create(
        Request $request,
        NurseRepository $nurseRepository,
        ValidatorInterface $validator,
    ): JsonResponse {
        try {
            $payload = $request->toArray();
        } catch (JsonException) {
            return $this->badRequest('El cuerpo de la petición debe ser un JSON válido');
        }

        $violations = $validator->validate($payload, $this->createNurseConstraint());

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[trim($violation->getPropertyPath(), '[]')] = $violation->getMessage();
            }

            return $this->badRequest('Datos de la enfermera inválidos', $errors);
        }

        $username = trim($payload['username']);
        $email = trim($payload['email']);

        if ($nurseRepository->findByUsername($username) !== null) {
            return $this->badRequest('Datos de la enfermera inválidos', ['username' => 'El username ya existe']);
        }

        if ($nurseRepository->findOneBy(['email' => $email]) !== null) {
            return $this->badRequest('Datos de la enfermera inválidos', ['email' => 'El email ya existe']);
        }

        $nurse = (new Nurse())
            ->setName(trim($payload['name']))
            ->setUsername($username)
            ->setPassword($payload['password'])
            ->setEmail($email)
            ->setProfileImage($payload['profileImage'] ?? null)
            ->setStatus($payload['status'] ?? self::DEFAULT_STATUS);

        try {
            $nurseRepository->save($nurse);
        } catch (UniqueConstraintViolationException) {
            return $this->badRequest('Datos de la enfermera inválidos', ['nurse' => 'El username o el email ya existen']);
        } catch (DBALException) {
            return $this->json([
                'success' => false,
                'message' => 'No se ha podido registrar la enfermera',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($this->normalizeNurse($nurse), JsonResponse::HTTP_CREATED);
    }

    #[Route('/index', name: 'app_nurse', methods: ['GET'])]
    public function getAll(NurseRepository $nurseRepository): JsonResponse
    {
        return $this->json(array_map(
            fn(Nurse $nurse): array => $this->normalizeNurse($nurse),
            $nurseRepository->findAll()
        ));
    }

    #[Route('/name/{name}', name: 'app_nurse_show_username', methods: ['GET'])]
    public function findByName(string $name, NurseRepository $nurseRepository): JsonResponse
    {
        $username = trim($name);

        if ($username === '') {
            return $this->json([
                'success' => false,
                'message' => 'El username no puede estar vacío',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $nurse = $nurseRepository->findByUsername($username);

        if ($nurse === null) {
            return $this->json([
                'success' => false,
                'message' => 'Enfermera no encontrada',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json($this->normalizeNurse($nurse));
    }

    #[Route('/login', name: 'app_nurse_login', methods: ['POST'])]
    public function login(Request $request, NurseRepository $nurseRepository): JsonResponse
    {
        $payload = $request->getPayload()->all();
        $username = $payload['username'] ?? null;
        $password = $payload['password'] ?? null;

        if (!is_string($username) || !is_string($password)) {
            return $this->json([
                'success' => false,
                'message' => 'Credenciales incorrectas',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (!$nurseRepository->validateCredentials($username, $password)) {
            return $this->json([
                'success' => false,
                'message' => 'Credenciales incorrectas',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'message' => 'Credenciales correctas',
        ]);
    }

    private function createNurseConstraint(): Assert\Collection
    {
        $requiredString = static fn(int $maxLength): Assert\Sequentially => new Assert\Sequentially([
            new Assert\Type('string'),
            new Assert\NotBlank(normalizer: 'trim'),
            new Assert\Length(max: $maxLength),
        ]);

        return new Assert\Collection(fields: [
            'name' => $requiredString(100),
            'username' => $requiredString(64),
            'password' => $requiredString(255),
            'email' => new Assert\Sequentially([
                new Assert\Type('string'),
                new Assert\NotBlank(normalizer: 'trim'),
                new Assert\Length(max: 180),
                new Assert\Email(normalizer: 'trim'),
            ]),
            'profileImage' => new Assert\Optional(new Assert\Sequentially([
                new Assert\Type('string'),
                new Assert\Length(max: 255),
            ])),
            'status' => new Assert\Optional($requiredString(20)),
        ]);
    }

    /**
     * @param array<string, string> $errors
     */
    private function badRequest(string $message, array $errors = []): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], JsonResponse::HTTP_BAD_REQUEST);
    }

    /**
     * @return array{id: ?int, name: ?string, username: ?string, createdAt: ?string,
     *     updatedAt: ?string, profileImage: ?string, email: ?string, status: ?string}
     */
    private function normalizeNurse(Nurse $nurse): array
    {
        return [
            'id' => $nurse->getId(),
            'name' => $nurse->getName(),
            'username' => $nurse->getUsername(),
            'createdAt' => $nurse->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $nurse->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'profileImage' => $nurse->getProfileImage(),
            'email' => $nurse->getEmail(),
            'status' => $nurse->getStatus(),
        ];
    }
}
