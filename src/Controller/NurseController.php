<?php

namespace App\Controller;

use App\Entity\Nurse;
use App\Entity\NurseCredential;
use App\Repository\NurseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/nurse')]
final class NurseController extends AbstractController
{
    #[Route('/nurse', name: 'app_nurse_controller')]
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
            fn (Nurse $nurse): array => $this->normalizeNurse($nurse),
            $nurseRepository->findAll()
        ));
    }
  
    #[Route('/name/{name}', name: 'app_nurse_show_name', methods: ['GET'])]
    public function findByName(string $name, NurseRepository $nurseRepository): JsonResponse
    {
        $name = trim($name);

        if ($name === '') {
            return $this->json([
                'success' => false,
                'message' => 'El nombre no puede estar vacío',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $nurse = $nurseRepository->findByName($name);

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
        $credentials = $request->getPayload()->all();
        $user = $credentials['user'] ?? null;
        $password = $credentials['password'] ?? null;

        if (!is_string($user) || !is_string($password)) {
            return $this->json([
                'success' => false,
                'message' => 'Credenciales incorrectas',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (!$nurseRepository->validateCredentials($user, $password)) {
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
     * @return array{user: ?string, credentials: list<array{
     *     licenseNumber: ?string,
     *     certification: ?string,
     *     issuingBody: ?string,
     *     expirationDate: ?string
     * }>}
     */
    private function normalizeNurse(Nurse $nurse): array
    {
        return [
            'user' => $nurse->getUser(),
            'credentials' => array_map(
                static fn (NurseCredential $credential): array => [
                    'licenseNumber' => $credential->getLicenseNumber(),
                    'certification' => $credential->getCertification(),
                    'issuingBody' => $credential->getIssuingBody(),
                    'expirationDate' => $credential->getExpirationDate()?->format('Y-m-d'),
                ],
                $nurse->getCredentials()->toArray()
            ),
        ];
    }
}
