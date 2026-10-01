<?php

namespace App\Controller;

use App\Dto\ProspectFormDTO;
use App\Entity\RelanceMotif;
use App\Form\ProspectType;
use App\Repository\ProductRepository;
use App\Repository\ProspectRepository;
use App\Repository\TeamRepository;
use App\Repository\UserRepository;
use App\Security\Permission;
use App\Service\ClientService;
use App\Service\ProspectDuplicateService;
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
        'app_traitement_mes_nouveaux',
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
        private readonly ProspectDuplicateService $duplicateService,
        private readonly ClientService $clientService,
        private readonly TeamRepository $teamRepository,
        private readonly UserRepository $userRepository,
        private readonly ProductRepository $productRepository,
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
            $prospect = $this->prospectService->createManually($dto, $this->getUser());
            $this->addFlash('info', sprintf('Prospect "%s" créé.', $prospect->getNom()));

            return $this->redirectToRoute('app_traitement_show', ['id' => $prospect->getId()]);
        }

        return $this->render('prospect/new.html.twig', ['form' => $form]);
    }

    #[IsGranted(Permission::NOUVEAU_PROSPECT->value)]
    #[Route('/nouveaux', name: 'nouveaux', methods: ['GET'])]
    public function nouveaux(Request $request): Response
    {
        $q = $this->q($request);
        $filters = $this->filters($request);

        return $this->renderCycle('Nouveaux prospects', $this->traitementService->nouveaux($this->page($request), $q, $filters), $request, $q, $filters);
    }

    #[IsGranted(Permission::MES_NOUVEAUX_PROSPECTS->value)]
    #[Route('/mes-nouveaux', name: 'mes_nouveaux', methods: ['GET'])]
    public function mesNouveaux(Request $request): Response
    {
        $q = $this->q($request);
        $filters = $this->filters($request);

        return $this->renderCycle('Mes nouveaux prospects', $this->traitementService->mesNouveaux($this->page($request), $q, $filters), $request, $q, $filters);
    }

    #[IsGranted(Permission::PROSPECTS_NON_TRAITES->value)]
    #[Route('/non-traites', name: 'non_traites', methods: ['GET'])]
    public function nonTraites(Request $request): Response
    {
        $q = $this->q($request);
        $filters = $this->filters($request);

        return $this->renderCycle('Prospects non traités', $this->traitementService->nonTraites($this->page($request), $q, $filters), $request, $q, $filters);
    }

    #[IsGranted(Permission::RELANCES_DU_JOUR->value)]
    #[Route('/relances-du-jour', name: 'relances_du_jour', methods: ['GET'])]
    public function relancesDuJour(Request $request): Response
    {
        $q = $this->q($request);
        $filters = $this->filters($request);

        return $this->renderCycle('Relances du jour', $this->traitementService->relancesDuJour($this->page($request), $q, $filters), $request, $q, $filters);
    }

    #[IsGranted(Permission::RELANCES_A_VENIR->value)]
    #[Route('/relances-a-venir', name: 'relances_a_venir', methods: ['GET'])]
    public function relancesAVenir(Request $request): Response
    {
        $q = $this->q($request);
        $filters = $this->filters($request);

        return $this->renderCycle('Relances à venir', $this->traitementService->relancesAVenir($this->page($request), $q, $filters), $request, $q, $filters);
    }

    #[IsGranted(Permission::RELANCES_NON_TRAITEES->value)]
    #[Route('/relances-non-traitees', name: 'relances_non_traitees', methods: ['GET'])]
    public function relancesNonTraitees(Request $request): Response
    {
        $q = $this->q($request);
        $filters = $this->filters($request);

        return $this->renderCycle('Relances non traitées', $this->traitementService->relancesNonTraitees($this->page($request), $q, $filters), $request, $q, $filters);
    }

    #[IsGranted(Permission::INJOIGNABLES->value)]
    #[Route('/injoignables', name: 'injoignables', methods: ['GET'])]
    public function injoignables(Request $request): Response
    {
        $q = $this->q($request);
        $filters = $this->filters($request);

        return $this->renderCycle('Injoignables', $this->traitementService->injoignables($this->page($request), $q, $filters), $request, $q, $filters);
    }

    #[IsGranted(Permission::RECHERCHE_PROSPECTS->value)]
    #[Route('/recherche', name: 'recherche', methods: ['GET'])]
    public function recherche(Request $request): Response
    {
        $q = $this->q($request);
        $filters = $this->filters($request);
        $resultats = $this->traitementService->search($q, $this->page($request), $filters);

        return $this->render('prospect/search.html.twig', array_merge([
            'q' => $q ?? '',
            'prospects' => $resultats['items'],
            'total' => $resultats['total'],
            'page' => $this->page($request),
            'motifs' => RelanceMotif::cases(),
            'teams' => $this->teamRepository->findAll(),
            'duplicates' => $this->duplicateService->detect($resultats['items']),
        ], $this->filterViewData($request)));
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
        $this->denyAccessUnlessGrantedAffectation();

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
        $this->denyAccessUnlessGrantedAffectation();

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

        $prochaineRelance = $this->parseProchaineRelance($request->request->get('prochaineRelance'));

        $this->traitementService->enregistrerRelance(
            $prospect,
            $motif,
            $request->request->get('comment'),
            $prochaineRelance,
            $this->getUser(),
        );

        if ($motif === RelanceMotif::PASSAGE_CLIENT) {
            try {
                $client = $this->clientService->convertFromProspect(
                    $prospect,
                    phone: $request->request->get('clientPhone') ?: null,
                    email: $request->request->get('clientEmail') ?: null,
                    raisonSociale: $request->request->get('clientRaisonSociale') ?: null,
                    adresse: $request->request->get('clientAdresse') ?: null,
                );
                $this->addFlash('info', sprintf('Client "%s" créé à partir du prospect.', $client->getDisplayName()));
            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        $this->addFlash('info', sprintf('Prospect "%s" traité : %s.', $prospect->getNom(), $motif->label()));

        $retour = $request->request->get('retour');
        if ($retour && \in_array($retour, self::ROUTES_CYCLE, true)) {
            return $this->redirectToRoute($retour);
        }

        return $this->redirectToRoute('app_traitement_show', ['id' => $prospect->getId()]);
    }

    /**
     * @param array{items: \App\Entity\Prospect[], total: int} $resultats
     * @param array{productId?: int, teamId?: int, commercialId?: int, relance?: int, source?: string, activites?: string, url?: string, createdFrom?: \DateTimeImmutable, createdTo?: \DateTimeImmutable} $filters
     */
    private function renderCycle(string $titre, array $resultats, Request $request, ?string $q = null, array $filters = []): Response
    {
        return $this->render('prospect/cycle.html.twig', array_merge([
            'titre' => $titre,
            'prospects' => $resultats['items'],
            'total' => $resultats['total'],
            'page' => $this->page($request),
            'q' => $q ?? '',
            'motifs' => RelanceMotif::cases(),
            'teams' => $this->teamRepository->findAll(),
            'duplicates' => $this->duplicateService->detect($resultats['items']),
        ], $this->filterViewData($request)));
    }

    /**
     * Parse strictement la valeur d'un champ <input type="datetime-local">
     * ("Y-m-d\TH:i"). Le constructeur lenient de DateTimeImmutable accepte
     * des chaînes partielles (ex: "15:45" sans date) en complétant les
     * champs manquants avec la date/heure courante — d'où le bug observé
     * où une relance programmée se retrouvait persistée à la date du jour.
     */
    private function parseProchaineRelance(?string $value): ?\DateTimeImmutable
    {
        if (!$value) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
        if ($date === false) {
            $this->addFlash('error', 'Date de relance invalide, ignorée.');

            return null;
        }

        return $date;
    }

    private function page(Request $request): int
    {
        return max(1, (int) $request->query->get('page', 1));
    }

    private function q(Request $request): ?string
    {
        $q = trim((string) $request->query->get('q', ''));

        return $q !== '' ? $q : null;
    }

    /**
     * Filtres bruts (produit/équipe/commercial/relance/intervalle de
     * création) lus depuis la query string, prêts à être passés aux
     * finders du repository — communs à tous les paniers de traitement
     * et à la recherche globale.
     *
     * @return array{productId?: int, teamId?: int, commercialId?: int, relance?: int, source?: string, activites?: string, url?: string, createdFrom?: \DateTimeImmutable, createdTo?: \DateTimeImmutable}
     */
    private function filters(Request $request): array
    {
        $filters = [];

        if ($this->isGranted('ROLE_ADMIN')) {
            if ($productId = $request->query->get('productId')) {
                $filters['productId'] = (int) $productId;
            }

            if ($teamId = $request->query->get('teamId')) {
                $filters['teamId'] = (int) $teamId;
            }

            if ($activites = $request->query->get('activites')) {
                $filters['activites'] = $activites;
            }

            if ($url = $request->query->get('url')) {
                $filters['url'] = $url;
            }
        }

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_CHEF_EQUIPE')) {
            if ($commercialId = $request->query->get('commercialId')) {
                $filters['commercialId'] = (int) $commercialId;
            }
        }

        if ($relance = $request->query->get('relance')) {
            $filters['relance'] = (int) $relance;
        }

        if ($source = $request->query->get('source')) {
            $filters['source'] = $source;
        }

        if ($createdFrom = $request->query->get('createdFrom')) {
            $filters['createdFrom'] = new \DateTimeImmutable($createdFrom . ' 00:00:00');
        }

        if ($createdTo = $request->query->get('createdTo')) {
            $filters['createdTo'] = new \DateTimeImmutable($createdTo . ' 23:59:59');
        }

        return $filters;
    }

    /**
     * Valeurs sélectionnées + listes de référence pour le formulaire de
     * filtres, à fusionner dans le contexte de rendu de chaque écran.
     */
    private function filterViewData(Request $request): array
    {
        return [
            'products' => $this->productRepository->findAll(),
            'commercials' => $this->commercialsForFilter($request),
            'productId' => $request->query->get('productId', ''),
            'teamId' => $request->query->get('teamId', ''),
            'commercialId' => $request->query->get('commercialId', ''),
            'relance' => $request->query->get('relance', ''),
            'source' => $request->query->get('source', ''),
            'activites' => $request->query->get('activites', ''),
            'url' => $request->query->get('url', ''),
            'createdFrom' => $request->query->get('createdFrom', ''),
            'createdTo' => $request->query->get('createdTo', ''),
        ];
    }

    /**
     * Commerciaux proposés dans le filtre "Commercial" : pour l'admin,
     * filtrés par l'équipe sélectionnée dans le filtre "Équipe" (tous si
     * aucune équipe choisie) ; pour le chef d'équipe, uniquement les
     * commerciaux de ses propres équipes (pas de sélecteur "Équipe" pour
     * lui) ; vide pour un simple ROLE_USER (champ masqué côté template).
     *
     * @return \App\Entity\User[]
     */
    private function commercialsForFilter(Request $request): array
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            if ($teamId = $request->query->get('teamId')) {
                $team = $this->teamRepository->find($teamId);

                return $team
                    ? array_values(array_filter($team->getUsers()->toArray(), fn ($user) => $user->isActif()))
                    : [];
            }

            return $this->userRepository->findAll();
        }

        if ($this->isGranted('ROLE_CHEF_EQUIPE')) {
            $commercials = [];
            foreach ($this->getUser()->getTeams() as $team) {
                foreach ($team->getUsers() as $user) {
                    if ($user->isActif()) {
                        $commercials[$user->getId()] = $user;
                    }
                }
            }

            return array_values($commercials);
        }

        return [];
    }

    /**
     * Affecter un prospect (équipe/commercial) est réservé à l'admin et au
     * chef d'équipe — un simple commercial n'a pas ce droit.
     */
    private function denyAccessUnlessGrantedAffectation(): void
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_CHEF_EQUIPE')) {
            throw $this->createAccessDeniedException("Vous n'avez pas les droits pour affecter un prospect.");
        }
    }
}
