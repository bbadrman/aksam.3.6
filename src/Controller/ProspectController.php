<?php

namespace App\Controller;

use App\Entity\RelanceMotif;
use App\Repository\ProspectRepository;
use App\Service\ProspectTraitementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/traitement', name: 'app_traitement_')]
class ProspectController extends AbstractController
{
    private const ROUTES_CYCLE = [
        'app_traitement_nouveaux',
        'app_traitement_non_traites',
        'app_traitement_relances_du_jour',
        'app_traitement_relances_a_venir',
        'app_traitement_relances_non_traitees',
        'app_traitement_injoignables',
    ];

    public function __construct(private readonly ProspectTraitementService $traitementService)
    {
    }

    #[Route('/nouveaux', name: 'nouveaux', methods: ['GET'])]
    public function nouveaux(Request $request): Response
    {
        return $this->renderCycle('Nouveaux prospects', $this->traitementService->nouveaux($this->page($request)), $request);
    }

    #[Route('/non-traites', name: 'non_traites', methods: ['GET'])]
    public function nonTraites(Request $request): Response
    {
        return $this->renderCycle('Prospects non traités', $this->traitementService->nonTraites($this->page($request)), $request);
    }

    #[Route('/relances-du-jour', name: 'relances_du_jour', methods: ['GET'])]
    public function relancesDuJour(Request $request): Response
    {
        return $this->renderCycle('Relances du jour', $this->traitementService->relancesDuJour($this->page($request)), $request);
    }

    #[Route('/relances-a-venir', name: 'relances_a_venir', methods: ['GET'])]
    public function relancesAVenir(Request $request): Response
    {
        return $this->renderCycle('Relances à venir', $this->traitementService->relancesAVenir($this->page($request)), $request);
    }

    #[Route('/relances-non-traitees', name: 'relances_non_traitees', methods: ['GET'])]
    public function relancesNonTraitees(Request $request): Response
    {
        return $this->renderCycle('Relances non traitées', $this->traitementService->relancesNonTraitees($this->page($request)), $request);
    }

    #[Route('/injoignables', name: 'injoignables', methods: ['GET'])]
    public function injoignables(Request $request): Response
    {
        return $this->renderCycle('Injoignables', $this->traitementService->injoignables($this->page($request)), $request);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, ProspectRepository $prospectRepository): Response
    {
        $prospect = $prospectRepository->find($id);
        if (!$prospect) {
            throw $this->createNotFoundException('Prospect introuvable.');
        }

        return $this->render('prospect/show.html.twig', [
            'prospect' => $prospect,
            'motifs' => RelanceMotif::cases(),
        ]);
    }

    #[Route('/{id}/traiter', name: 'traiter', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function traiter(int $id, Request $request, ProspectRepository $prospectRepository): Response
    {
        $prospect = $prospectRepository->find($id);
        if (!$prospect) {
            throw $this->createNotFoundException('Prospect introuvable.');
        }

        $motif = RelanceMotif::tryFrom((int) $request->request->get('motif'));
        if (!$motif) {
            $this->addFlash('error', 'Motif de relance invalide.');

            return $this->redirectToRoute('app_traitement_nouveaux');
        }

        $prochaineRelanceStr = $request->request->get('prochaineRelance');
        $prochaineRelance = $prochaineRelanceStr ? new \DateTimeImmutable($prochaineRelanceStr) : null;

        $this->traitementService->enregistrerRelance(
            $prospect,
            $motif,
            $request->request->get('comment'),
            $prochaineRelance,
            $this->getUser(),
        );

        $this->addFlash('info', sprintf('Prospect "%s" traité : %s.', $prospect->getNom(), $motif->label()));

        $retour = $request->request->get('retour');
        if ($retour && \in_array($retour, self::ROUTES_CYCLE, true)) {
            return $this->redirectToRoute($retour);
        }

        return $this->redirectToRoute('app_traitement_show', ['id' => $prospect->getId()]);
    }

    /**
     * @param array{items: \App\Entity\Prospect[], total: int} $resultats
     */
    private function renderCycle(string $titre, array $resultats, Request $request): Response
    {
        return $this->render('prospect/cycle.html.twig', [
            'titre' => $titre,
            'prospects' => $resultats['items'],
            'total' => $resultats['total'],
            'page' => $this->page($request),
            'motifs' => RelanceMotif::cases(),
        ]);
    }

    private function page(Request $request): int
    {
        return max(1, (int) $request->query->get('page', 1));
    }
}
