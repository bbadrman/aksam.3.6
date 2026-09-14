<?php

namespace App\Controller;

use App\Entity\Team;
use App\Form\TeamType;
use App\Repository\TeamRepository;
use App\Service\TeamService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/equipes', name: 'app_team_')]
class TeamController extends AbstractController
{
    public function __construct(private readonly TeamService $teamService)
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(TeamRepository $teamRepository): Response
    {
        return $this->render('team/index.html.twig', [
            'teams' => $teamRepository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/nouvelle', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $team = new Team();
        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->teamService->create($team);
            $this->addFlash('info', sprintf('Équipe "%s" créée.', $team->getNom()));

            return $this->redirectToRoute('app_team_index');
        }

        return $this->render('team/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/editer', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request, TeamRepository $teamRepository): Response
    {
        $team = $teamRepository->find($id);
        if (!$team) {
            throw $this->createNotFoundException('Équipe introuvable.');
        }

        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->teamService->update($team);
            $this->addFlash('info', sprintf('Équipe "%s" mise à jour.', $team->getNom()));

            return $this->redirectToRoute('app_team_index');
        }

        return $this->render('team/edit.html.twig', ['form' => $form, 'team' => $team]);
    }
}
