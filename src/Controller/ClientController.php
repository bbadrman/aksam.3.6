<?php

namespace App\Controller;

use App\Form\ClientStatusType;
use App\Repository\ClientRepository;
use App\Repository\ProspectRepository;
use App\Service\ClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/{id}', name: 'show', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        Request $request,
        ClientRepository $clientRepository,
        ClientService $clientService,
    ): Response {
        $client = $clientRepository->find($id);
        if (!$client) {
            throw $this->createNotFoundException('Client introuvable.');
        }

        $form = $this->createForm(ClientStatusType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $clientService->decider($client, $form->get('status')->getData());
                $this->addFlash('info', sprintf('Client "%s" : %s.', $client->getDisplayName(), mb_strtolower($client->getStatusLabel())));
            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
        }

        return $this->render('client/show.html.twig', [
            'client' => $client,
            'form' => $form,
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
