<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\ContratRepository;
use App\Repository\SavRepository;
use App\Service\ProspectTraitementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        ProspectTraitementService $traitementService,
        ClientRepository $clientRepository,
        ContratRepository $contratRepository,
        SavRepository $savRepository,
    ): Response {
        return $this->render('home/index.html.twig', [
            'nouveauxCount' => $traitementService->nouveaux()['total'],
            'nonTraitesCount' => $traitementService->nonTraites()['total'],
            'relancesDuJourCount' => $traitementService->relancesDuJour()['total'],
            'relancesAVenirCount' => $traitementService->relancesAVenir()['total'],
            'injoignablesCount' => $traitementService->injoignables()['total'],
            'relancesNonTraiteesCount' => $traitementService->relancesNonTraitees()['total'],
            'clientsCount' => \count($clientRepository->findAll()),
            'contratsCount' => \count($contratRepository->findAll()),
            'savOuvertsCount' => \count($savRepository->findBy(['statut' => 'ouvert'])),
        ]);
    }
}
