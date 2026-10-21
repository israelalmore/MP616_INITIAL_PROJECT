<?php

namespace App\Controller;

use App\Entity\Nurse;
use App\Repository\NurseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

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
