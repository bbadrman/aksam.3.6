<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\Prospect;
use App\Entity\RelanceMotif;
use App\Entity\Team;
use App\Security\ProspectScope;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prospect>
 */
class ProspectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prospect::class);
    }

    /**
     * Recherche globale (tous cycles confondus), respectant le scope de
     * l'utilisateur courant. Filtre texte libre sur nom/prénom/téléphone/
     * email, combinable avec les filtres habituels.
     *
     * @param array{productId?: int, teamId?: int, commercialId?: int, relance?: int, source?: string, activites?: string, url?: string, createdFrom?: \DateTimeImmutable, createdTo?: \DateTimeImmutable} $filters
     * @return array{items: Prospect[], total: int}
     */
    public function search(ProspectScope $scope, ?string $q, array $filters = [], int $page = 1, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('team', 'commercial', 'autor')
            ->leftJoin('p.team', 'team')
            ->leftJoin('p.commercial', 'commercial')
            ->leftJoin('p.autor', 'autor')
            ->orderBy('p.createdAt', 'DESC');

        $this->applyScope($qb, $scope);
        $this->applyQ($qb, $q);
        $this->applyFilters($qb, $filters);

        return $this->paginate($qb, $page, $limit);
    }

    public function countByScope(ProspectScope $scope): int
    {
        $qb = $this->createQueryBuilder('p')->select('COUNT(p.id)');
        $this->applyScope($qb, $scope);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Charge de travail actuelle d'une équipe sur un produit donné — sert de
     * base à la distribution automatique round-robin des prospects entrants
     * (App\Service\ProspectDistributionService). "Actif" = pas encore
     * converti en client.
     */
    public function countActifsByTeamAndProduct(Team $team, Product $product): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.team = :team')
            ->andWhere('p.product = :product')
            ->andWhere('p.statut != :converti')
            ->setParameter('team', $team)
            ->setParameter('product', $product)
            ->setParameter('converti', Prospect::STATUT_CONVERTI)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Répartition produit × équipe des prospects déjà affectés
     * (`affectAt IS NOT NULL`), pour le dashboard de distribution.
     * Chaque ligne : product_id, product_nom, team_id, team_nom, total,
     * converted (statut converti), pending (sans commercial attitré).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDistributionStats(?Product $product = null, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select(
                'pr.id AS product_id',
                'pr.nom AS product_nom',
                't.id AS team_id',
                't.nom AS team_nom',
                'COUNT(p.id) AS total',
                'SUM(CASE WHEN p.statut = :converti THEN 1 ELSE 0 END) AS converted',
                'SUM(CASE WHEN p.commercial IS NULL THEN 1 ELSE 0 END) AS pending',
            )
            ->innerJoin('p.product', 'pr')
            ->innerJoin('p.team', 't')
            ->andWhere('p.affectAt IS NOT NULL')
            ->setParameter('converti', Prospect::STATUT_CONVERTI)
            ->groupBy('pr.id', 't.id')
            ->orderBy('pr.nom', 'ASC')
            ->addOrderBy('t.nom', 'ASC');

        $this->applyDistributionFilters($qb, $product, $from, $to);

        return $qb->getQuery()->getResult();
    }

    /**
     * Répartition équipe × commercial des prospects affectés, pour le
     * classement des commerciaux dans le dashboard de distribution.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCommercialDistributionStats(?Product $product = null, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select(
                't.id AS team_id',
                'u.id AS user_id',
                'u.username AS user_username',
                'u.nom AS user_nom',
                'u.prenom AS user_prenom',
                'COUNT(p.id) AS total',
                'SUM(CASE WHEN p.statut = :converti THEN 1 ELSE 0 END) AS converted',
            )
            ->innerJoin('p.team', 't')
            ->innerJoin('p.commercial', 'u')
            ->andWhere('p.affectAt IS NOT NULL')
            ->setParameter('converti', Prospect::STATUT_CONVERTI)
            ->groupBy('t.id', 'u.id')
            ->orderBy('t.nom', 'ASC')
            ->addOrderBy('total', 'DESC');

        $this->applyDistributionFilters($qb, $product, $from, $to);

        return $qb->getQuery()->getResult();
    }

    /**
     * Répartition équipe × commercial × produit des prospects affectés,
     * pour le détail par produit de chaque commercial.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCommercialProductStats(?Product $product = null, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select(
                't.id AS team_id',
                'u.id AS user_id',
                'pr.id AS product_id',
                'pr.nom AS product_nom',
                'COUNT(p.id) AS total',
                'SUM(CASE WHEN p.statut = :converti THEN 1 ELSE 0 END) AS converted',
            )
            ->innerJoin('p.team', 't')
            ->innerJoin('p.commercial', 'u')
            ->innerJoin('p.product', 'pr')
            ->andWhere('p.affectAt IS NOT NULL')
            ->setParameter('converti', Prospect::STATUT_CONVERTI)
            ->groupBy('t.id', 'u.id', 'pr.id')
            ->orderBy('t.nom', 'ASC')
            ->addOrderBy('u.username', 'ASC')
            ->addOrderBy('pr.nom', 'ASC');

        $this->applyDistributionFilters($qb, $product, $from, $to);

        return $qb->getQuery()->getResult();
    }

    private function applyDistributionFilters(QueryBuilder $qb, ?Product $product, ?\DateTimeInterface $from, ?\DateTimeInterface $to): void
    {
        if ($product !== null) {
            $qb->andWhere('p.product = :product')->setParameter('product', $product);
        }
        if ($from !== null) {
            $qb->andWhere('p.affectAt >= :from')->setParameter('from', $from);
        }
        if ($to !== null) {
            $qb->andWhere('p.affectAt <= :to')->setParameter('to', $to);
        }
    }

    /**
     * Cycle "Nouveaux prospects" : jamais traités (`p.relance IS NULL`), et
     * pas encore descendus à un commercial précis — pour l'admin, en plus
     * jamais affectés à une équipe (`team`/`commercial` nuls, c'est lui qui
     * distribue) ; pour un chef, affectés à son équipe (via applyScope())
     * mais toujours "en vrac", sans commercial attitré. Dès qu'un
     * commercial précis est affecté (que ce soit par l'admin ou par le
     * chef qui se l'attribue), le prospect quitte ce panier. Le panier
     * personnel "Mes nouveaux prospects" réutilise cette même méthode avec
     * un ProspectScope::forCommercial() : aucune condition supplémentaire
     * ne s'applique alors, applyScope() ayant déjà filtré sur le
     * commercial courant.
     *
     * @return array{items: Prospect[], total: int}
     */
    public function findNouveaux(ProspectScope $scope, int $page = 1, int $limit = 10, ?string $q = null, array $filters = []): array
    {
        $qb = $this->baseQueryBuilder($scope)
            ->andWhere('p.relance IS NULL');

        if ($scope->unrestricted) {
            $qb->andWhere('p.team IS NULL')->andWhere('p.commercial IS NULL');
        } elseif ($scope->teamIds !== null) {
            $qb->andWhere('p.commercial IS NULL');
        }

        $this->applyQ($qb, $q);
        $this->applyFilters($qb, $filters);
        $qb->orderBy('p.createdAt', 'DESC');

        return $this->paginate($qb, $page, $limit);
    }

    /**
     * Cycle "Non traités" : affectés à une équipe depuis plus de 24h mais
     * jamais encore relancés.
     *
     * @return array{items: Prospect[], total: int}
     */
    public function findNonTraites(ProspectScope $scope, \DateTimeImmutable $avant, int $page = 1, int $limit = 10, ?string $q = null, array $filters = []): array
    {
        $qb = $this->baseQueryBuilder($scope)
            ->andWhere('p.team IS NOT NULL')
            ->andWhere('p.relance IS NULL')
            ->andWhere('p.affectAt IS NOT NULL')
            ->andWhere('p.affectAt <= :avant')
            ->setParameter('avant', $avant)
            ->orderBy('p.affectAt', 'ASC');

        $this->applyQ($qb, $q);
        $this->applyFilters($qb, $filters);

        return $this->paginate($qb, $page, $limit);
    }

    /**
     * Cycle "Relances du jour" : une relance est planifiée aujourd'hui,
     * fiche pas encore clôturée.
     *
     * @return array{items: Prospect[], total: int}
     */
    public function findRelancesDuJour(ProspectScope $scope, \DateTimeImmutable $debutJour, \DateTimeImmutable $finJour, int $page = 1, int $limit = 10, ?string $q = null, array $filters = []): array
    {
        $qb = $this->baseQueryBuilder($scope)
            ->andWhere('p.relanceAt BETWEEN :debut AND :fin')
            ->andWhere($this->excludeCloturesExpr())
            ->setParameter('debut', $debutJour)
            ->setParameter('fin', $finJour)
            ->setParameter('clotures', RelanceMotif::valeursCloturees())
            ->orderBy('p.relanceAt', 'ASC');

        $this->applyQ($qb, $q);
        $this->applyFilters($qb, $filters);

        return $this->paginate($qb, $page, $limit);
    }

    /**
     * Cycle "Relances à venir" : planifiées après aujourd'hui, fiche pas
     * clôturée.
     *
     * @return array{items: Prospect[], total: int}
     */
    public function findRelancesAVenir(ProspectScope $scope, \DateTimeImmutable $apres, int $page = 1, int $limit = 10, ?string $q = null, array $filters = []): array
    {
        $qb = $this->baseQueryBuilder($scope)
            ->andWhere('p.relanceAt > :apres')
            ->andWhere($this->excludeCloturesExpr())
            ->setParameter('apres', $apres)
            ->setParameter('clotures', RelanceMotif::valeursCloturees())
            ->orderBy('p.relanceAt', 'ASC');

        $this->applyQ($qb, $q);
        $this->applyFilters($qb, $filters);

        return $this->paginate($qb, $page, $limit);
    }

    /**
     * Cycle "Relances non traitées" : la date de relance planifiée est
     * dépassée sans qu'un nouvel appel n'ait été enregistré, fiche pas
     * clôturée.
     *
     * @return array{items: Prospect[], total: int}
     */
    public function findRelancesNonTraitees(ProspectScope $scope, \DateTimeImmutable $avant, int $page = 1, int $limit = 10, ?string $q = null, array $filters = []): array
    {
        $qb = $this->baseQueryBuilder($scope)
            ->andWhere('p.relanceAt IS NOT NULL')
            ->andWhere('p.relanceAt <= :avant')
            ->andWhere($this->excludeCloturesExpr())
            ->setParameter('avant', $avant)
            ->setParameter('clotures', RelanceMotif::valeursCloturees())
            ->orderBy('p.relanceAt', 'ASC');

        $this->applyQ($qb, $q);
        $this->applyFilters($qb, $filters);

        return $this->paginate($qb, $page, $limit);
    }

    /**
     * Cycle "Injoignables" : motif "Toujours injoignable" (distinct du
     * premier échec "Injoignable", qui reste dans le circuit normal).
     *
     * @return array{items: Prospect[], total: int}
     */
    public function findInjoignables(ProspectScope $scope, int $page = 1, int $limit = 10, ?string $q = null, array $filters = []): array
    {
        $qb = $this->baseQueryBuilder($scope)
            ->andWhere('p.relance = :motif')
            ->setParameter('motif', RelanceMotif::TOUJOURS_INJOIGNABLE)
            ->orderBy('p.relanceAt', 'DESC');

        $this->applyQ($qb, $q);
        $this->applyFilters($qb, $filters);

        return $this->paginate($qb, $page, $limit);
    }

    /**
     * Emails apparaissant plus d'une fois parmi la liste donnée (une seule
     * requête groupée, peu importe le nombre de prospects affichés).
     *
     * @param string[] $emails
     * @return string[]
     */
    public function findDuplicateEmails(array $emails): array
    {
        if ($emails === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('p')
            ->select('p.email AS email')
            ->where('p.email IN (:emails)')
            ->setParameter('emails', $emails)
            ->groupBy('p.email')
            ->having('COUNT(p.id) > 1')
            ->getQuery()
            ->getResult();

        return array_column($rows, 'email');
    }

    /**
     * Numéros de téléphone (valeurs brutes telles que stockées en base)
     * correspondant à l'une des variantes d'écriture données — le
     * regroupement par forme normalisée est fait côté appelant, une
     * requête ne sachant pas normaliser les numéros en SQL.
     *
     * @param string[] $phoneVariants
     * @return string[]
     */
    public function findPhonesMatchingVariants(array $phoneVariants): array
    {
        if ($phoneVariants === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('p')
            ->select('p.phone AS phone')
            ->where('p.phone IN (:phones)')
            ->setParameter('phones', $phoneVariants)
            ->getQuery()
            ->getResult();

        return array_column($rows, 'phone');
    }

    private function applyQ(QueryBuilder $qb, ?string $q): void
    {
        if ($q === null || $q === '') {
            return;
        }

        $qb->andWhere('p.nom LIKE :q OR p.prenom LIKE :q OR p.phone LIKE :q OR p.email LIKE :q OR p.raisonSociale LIKE :q')
            ->setParameter('q', '%' . $q . '%');
    }

    /**
     * @param array{productId?: int, teamId?: int, commercialId?: int, relance?: int, source?: string, activites?: string, url?: string, createdFrom?: \DateTimeImmutable, createdTo?: \DateTimeImmutable} $filters
     */
    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['productId'])) {
            $qb->andWhere('p.product = :productId')->setParameter('productId', $filters['productId']);
        }

        if (!empty($filters['source'])) {
            $qb->andWhere('p.source = :source')->setParameter('source', $filters['source']);
        }

        if (!empty($filters['activites'])) {
            $qb->andWhere('p.activites = :activites')->setParameter('activites', $filters['activites']);
        }

        if (!empty($filters['url'])) {
            $qb->andWhere('p.url = :url')->setParameter('url', $filters['url']);
        }

        if (!empty($filters['teamId'])) {
            $qb->andWhere('p.team = :teamId')->setParameter('teamId', $filters['teamId']);
        }

        if (!empty($filters['commercialId'])) {
            $qb->andWhere('p.commercial = :commercialId')->setParameter('commercialId', $filters['commercialId']);
        }

        if (!empty($filters['relance'])) {
            $qb->andWhere('p.relance = :relanceFiltre')->setParameter('relanceFiltre', $filters['relance']);
        }

        if (!empty($filters['createdFrom'])) {
            $qb->andWhere('p.createdAt >= :createdFrom')->setParameter('createdFrom', $filters['createdFrom']);
        }

        if (!empty($filters['createdTo'])) {
            $qb->andWhere('p.createdAt <= :createdTo')->setParameter('createdTo', $filters['createdTo']);
        }
    }

    private function excludeCloturesExpr(): string
    {
        return '(p.relance IS NULL OR p.relance NOT IN (:clotures))';
    }

    private function baseQueryBuilder(ProspectScope $scope): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('team', 'commercial', 'autor')
            ->leftJoin('p.team', 'team')
            ->leftJoin('p.commercial', 'commercial')
            ->leftJoin('p.autor', 'autor');

        return $this->applyScope($qb, $scope);
    }

    private function applyScope(QueryBuilder $qb, ProspectScope $scope): QueryBuilder
    {
        if ($scope->unrestricted) {
            return $qb;
        }

        if ($scope->teamIds !== null) {
            return $qb->andWhere('p.team IN (:teamIds)')->setParameter('teamIds', $scope->teamIds);
        }

        return $qb->andWhere('p.commercial = :commercial')->setParameter('commercial', $scope->commercial);
    }

    /**
     * @return array{items: Prospect[], total: int}
     */
    private function paginate(QueryBuilder $qb, int $page, int $limit): array
    {
        $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);
        $paginator = new Paginator($qb, fetchJoinCollection: false);

        return [
            'items' => iterator_to_array($paginator->getIterator()),
            'total' => count($paginator),
        ];
    }
}
