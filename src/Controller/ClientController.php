<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\ProspectRepository;
use App\Service\ClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/clients', name: 'app_client_')]
class ClientController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ClientRepository $clientRepository): Response
    {
        return $this->render('client/index.html.twig', [
            'clients' => $clientRepository->findBy([], ['createdAt' => 'DESC'], 50),
        ]);
    }

    #[Route('/depuis-prospect/{id}', name: 'convert_from_prospect', methods: ['POST'])]
    public function convertFromProspect(
        int $id,
        ProspectRepository $prospectRepository,
        ClientService $clientService,
    ): Response {
        $prospect = $prospectRepository->find($id);
        if (!$prospect) {
            throw $this->createNotFoundException('Prospect introuvable.');
        }

        try {
            $client = $clientService->convertFromProspect($prospect);
        } catch (\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_home');
        }

        $this->addFlash('info', sprintf('Client "%s" créé à partir du prospect.', $client->getDisplayName()));

        return $this->redirectToRoute('app_client_index');
    }
}
