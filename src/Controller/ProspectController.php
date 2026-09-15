<?php

namespace App\Controller;

use App\Dto\ProspectFormDTO;
use App\Entity\RelanceMotif;
use App\Form\ProspectType;
use App\Repository\ProspectRepository;
use App\Repository\TeamRepository;
use App\Repository\UserRepository;
use App\Security\Permission;
use App\Service\ProspectService;
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
        'app_traitement_recherche',
    ];

    public function __construct(
        private readonly ProspectTraitementService $traitementService,
        private readonly ProspectService $prospectService,
        private readonly TeamRepository $teamRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[IsGranted(Permission::CREER_PROSPECT->value)]
    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $dto = new ProspectFormDTO();
        $form = $this->createForm(ProspectType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $prospect = $this->prospectService->createManually($dto);
            $this->addFlash('info', sprintf('Prospect "%s" créé.', $prospect->getNom()));

            return $this->redirectToRoute('app_traitement_show', ['id' => $prospect->getId()]);
        }

        return $this->render('prospect/new.html.twig', ['form' => $form]);
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

    #[Route('/recherche', name: 'recherche', methods: ['GET'])]
    public function recherche(Request $request): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $resultats = $this->traitementService->search($q !== '' ? $q : null, $this->page($request));

        return $this->render('prospect/search.html.twig', [
            'q' => $q,
            'prospects' => $resultats['items'],
            'total' => $resultats['total'],
            'page' => $this->page($request),
            'motifs' => RelanceMotif::cases(),
            'teams' => $this->teamRepository->findAll(),
        ]);
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
            'teams' => $this->teamRepository->findAll(),
        ]);
    }

    /**
     * Équipes + commerciaux qui leur sont rattachés, pour filtrer
     * dynamiquement le select "Commercial" de la modale d'affectation
     * selon l'équipe choisie (même principe que l'ancien
     * TeamController::teamsApi()).
     */
    #[Route('/api/equipes-commerciaux', name: 'teams_commerciaux', methods: ['GET'])]
    public function teamsCommerciaux(): Response
    {
        $data = array_map(fn ($team) => [
            'id' => $team->getId(),
            'nom' => $team->getNom(),
            'commercials' => array_values(array_map(
                fn ($user) => ['id' => $user->getId(), 'username' => $user->getUsername()],
                array_filter($team->getUsers()->toArray(), fn ($user) => $user->isActif()),
            )),
        ], $this->teamRepository->findAll());

        return $this->json($data);
    }

    #[Route('/{id}/affecter', name: 'affecter', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function affecter(int $id, Request $request, ProspectRepository $prospectRepository): Response
    {
        $prospect = $prospectRepository->find($id);
        if (!$prospect) {
            throw $this->createNotFoundException('Prospect introuvable.');
        }

        $teamId = $request->request->get('team');
        $commercialId = $request->request->get('commercial');

        $team = $teamId ? $this->teamRepository->find($teamId) : null;
        $commercial = $commercialId ? $this->userRepository->find($commercialId) : null;

        $this->prospectService->affecter($prospect, $team, $commercial);

        $this->addFlash('info', sprintf(
            'Prospect "%s" affecté%s%s.',
            $prospect->getNom(),
            $team ? ' à l\'équipe ' . $team->getNom() : '',
            $commercial ? ' (commercial : ' . $commercial->getUsername() . ')' : '',
        ));

        $retour = $request->request->get('retour');
        if ($retour && \in_array($retour, self::ROUTES_CYCLE, true)) {
            return $this->redirectToRoute($retour);
        }

        return $this->redirectToRoute('app_traitement_show', ['id' => $prospect->getId()]);
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
            'teams' => $this->teamRepository->findAll(),
        ]);
    }

    private function page(Request $request): int
    {
        return max(1, (int) $request->query->get('page', 1));
    }
}
