<?php

namespace App\Controller\Api;

use App\Repository\CompartenaireRepository;
use App\Service\ProspectService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ProspectApiController extends AbstractController
{
    #[Route('/api/prospects', name: 'api_prospect_create', methods: ['POST'])]
    public function create(
        Request $request,
        CompartenaireRepository $compartenaireRepository,
        ProspectService $prospectService,
    ): JsonResponse {
        $token = $request->headers->get('X-API-TOKEN');
        if (!$token) {
            return $this->json(['error' => 'Token API manquant.'], 401);
        }

        $partenaire = $compartenaireRepository->findOneByApiToken($token);
        if (!$partenaire) {
            return $this->json(['error' => 'Token API invalide.'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Corps de requête JSON invalide.'], 400);
        }

        if (trim((string) ($data['nom'] ?? '')) === '' || trim((string) ($data['phone'] ?? '')) === '') {
            return $this->json(['error' => 'Les champs "nom" et "phone" sont obligatoires.'], 400);
        }

        try {
            $prospect = $prospectService->createFromApiPayload($partenaire, $data);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        }

        return $this->json(['id' => $prospect->getId(), 'status' => 'created'], 201);
    }
}
