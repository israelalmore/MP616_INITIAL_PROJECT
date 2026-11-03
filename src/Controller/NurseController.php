<?php

namespace App\Controller;

use App\Entity\Nurse;
use App\Repository\NurseRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\UnicodeString;
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

    #[Route('/delete/{nurseId<\d+>}', name: 'app_nurse_delete', methods: ['DELETE'])]
    public function delete(int $nurseId, EntityManagerInterface $entityManager): JsonResponse
    {
        $nurse = $entityManager->find(Nurse::class, $nurseId);
        if (!$nurse instanceof Nurse) {
            return $this->json([
                'success' => false,
                'message' => 'Enfermera no encontrada',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($nurse);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Enfermera eliminada',
        ]);
    }

    #[Route('/{nurseId<\d+>}', name: 'app_nurse_update', methods: ['PUT'])]
    public function update(
        int $nurseId,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON body',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!$data instanceof \stdClass) {
            return $this->json([
                'success' => false,
                'message' => 'The request body must be a JSON object',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $payload = get_object_vars($data);
        $errors = [];
        $values = [];
        $requiredFields = [
            'name' => 100,
            'username' => 64,
            'email' => 180,
            'status' => 20,
        ];
        $allowedFields = [...array_keys($requiredFields), 'profileImage', 'password'];

        foreach (array_diff(array_keys($payload), $allowedFields) as $field) {
            $errors[$field] = 'This field is not allowed';
        }

        foreach ($requiredFields as $field => $maxLength) {
            if (!isset($payload[$field]) || !is_string($payload[$field])) {
                $errors[$field] = 'This field is required and must be a string';
                continue;
            }

            $value = trim($payload[$field]);
            if ($value === '') {
                $errors[$field] = 'This field cannot be blank';
                continue;
            }

            if ((new UnicodeString($value))->length() > $maxLength) {
                $errors[$field] = 'This field is too long';
                continue;
            }

            $values[$field] = $value;
        }

        if (isset($values['email']) && filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'This field must be a valid email address';
        }

        if (array_key_exists('profileImage', $payload)) {
            $profileImage = $payload['profileImage'];
            if ($profileImage !== null && (!is_string($profileImage) || (new UnicodeString($profileImage))->length() > 255)) {
                $errors['profileImage'] = 'This field must be a string of at most 255 characters or null';
            } else {
                $values['profileImage'] = $profileImage;
            }
        }

        if (array_key_exists('password', $payload)) {
            $password = $payload['password'];
            if (!is_string($password) || trim($password) === '' || (new UnicodeString($password))->length() > 255) {
                $errors['password'] = 'This field must be a non-blank string of at most 255 characters';
            } else {
                $values['password'] = $password;
            }
        }

        if ($errors !== []) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid nurse data',
                'errors' => $errors,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $nurse = $entityManager->find(Nurse::class, $nurseId);
        if (!$nurse instanceof Nurse) {
            return $this->json([
                'success' => false,
                'message' => 'Enfermera no encontrada',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $nurse
            ->setName($values['name'])
            ->setUsername($values['username'])
            ->setEmail($values['email'])
            ->setStatus($values['status']);

        if (array_key_exists('profileImage', $values)) {
            $nurse->setProfileImage($values['profileImage']);
        }

        if (array_key_exists('password', $values)) {
            $nurse->setPassword($values['password']);
        }

        try {
            $entityManager->persist($nurse);
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            return $this->json([
                'success' => false,
                'message' => 'Username or email is already in use',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return $this->json($this->normalizeNurse($nurse));
    }

    #[Route('/id/{nurseId<\d+>}', name: 'app_nurse_show_id', methods: ['GET'])]
    public function findById(int $nurseId, NurseRepository $nurseRepository): JsonResponse
    {
        $nurse = $nurseRepository->find($nurseId);

        if (!$nurse instanceof Nurse) {
            return $this->json([
                'success' => false,
                'message' => 'Enfermera no encontrada',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json($this->normalizeNurse($nurse));
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
