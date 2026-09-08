<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\ContratRepository;
use App\Repository\ProspectRepository;
use App\Repository\SavRepository;
use App\Security\ProspectScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        ProspectRepository $prospectRepository,
        ClientRepository $clientRepository,
        ContratRepository $contratRepository,
        SavRepository $savRepository,
    ): Response {
        $scope = ProspectScope::all();

        return $this->render('home/index.html.twig', [
            'nouveauxCount' => $prospectRepository->findNouveaux($scope, 1, 1)['total'],
            'relancesDuJourCount' => $prospectRepository->findRelancesDuJour($scope, new \DateTimeImmutable('today'), new \DateTimeImmutable('today 23:59:59'), 1, 1)['total'],
            'clientsCount' => \count($clientRepository->findAll()),
            'contratsCount' => \count($contratRepository->findAll()),
            'savOuvertsCount' => \count($savRepository->findBy(['statut' => 'ouvert'])),
        ]);
    }
}
