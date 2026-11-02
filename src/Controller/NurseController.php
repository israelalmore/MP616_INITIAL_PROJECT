<?php

namespace App\Controller;

use App\Entity\Nurse;
use App\Repository\NurseRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\UnicodeString;

#[Route('/nurse')]
final class NurseController extends AbstractController
{
    #[Route('', name: 'app_nurse_controller')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => "Welcome to the new Controller",
            'path' => "src/Controller/NurseController.php",
        ]);
    }

    #[Route('/index', name: 'app_nurse', methods: ['GET'])]
    public function getAll(NurseRepository $nurseRepository): JsonResponse
    {
        return $this->json(array_map(
            fn(Nurse $nurse): array => $this->normalizeNurse($nurse),
            $nurseRepository->findAll()
        ));
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
