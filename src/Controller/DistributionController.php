<?php

namespace App\Controller;

use App\Entity\TeamProductLimit;
use App\Repository\ProductRepository;
use App\Repository\ProspectRepository;
use App\Repository\TeamProductLimitRepository;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/distribution')]
class DistributionController extends AbstractController
{
    public function __construct(
        private readonly TeamRepository $teamRepository,
        private readonly ProductRepository $productRepository,
        private readonly ProspectRepository $prospectRepository,
        private readonly TeamProductLimitRepository $limitRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Dashboard de suivi de la distribution automatique : répartition des
     * prospects affectés par produit / équipe / commercial sur une période
     * donnée (mois courant par défaut).
     */
    #[Route('', name: 'app_distribution_dashboard', methods: ['GET'])]
    public function dashboard(Request $request): Response
    {
        $productId = $request->query->get('product');
        $fromRaw = $request->query->get('from');
        $toRaw = $request->query->get('to');

        $selectedProduct = $productId ? $this->productRepository->find($productId) : null;

        if ($fromRaw && $toRaw) {
            $from = (new \DateTimeImmutable($fromRaw))->setTime(0, 0, 0);
            $to = (new \DateTimeImmutable($toRaw))->setTime(23, 59, 59);
        } else {
            $from = (new \DateTimeImmutable('first day of this month'))->setTime(0, 0, 0);
            $to = (new \DateTimeImmutable('last day of this month'))->setTime(23, 59, 59);
            $fromRaw = $from->format('Y-m-d');
            $toRaw = $to->format('Y-m-d');
        }

        $rows = $this->prospectRepository->getDistributionStats($selectedProduct, $from, $to);
        $commercialRows = $this->prospectRepository->getCommercialDistributionStats($selectedProduct, $from, $to);
        $commercialProdRows = $this->prospectRepository->getCommercialProductStats($selectedProduct, $from, $to);

        // [product_id => ['nom', 'total', 'converted', 'active', 'pending', 'teams' => [team_id => [...]]]]
        $data = [];
        // agrégat cross-produit par équipe, pour le classement global
        $teamTotals = [];

        foreach ($rows as $row) {
            $pid = $row['product_id'];
            $tid = $row['team_id'];
            $total = (int) $row['total'];
            $converted = (int) $row['converted'];
            $pending = (int) $row['pending'];
            $active = $total - $converted;

            if (!isset($data[$pid])) {
                $data[$pid] = ['nom' => $row['product_nom'], 'total' => 0, 'converted' => 0, 'active' => 0, 'pending' => 0, 'teams' => []];
            }
            $data[$pid]['teams'][$tid] = [
                'nom' => $row['team_nom'],
                'total' => $total,
                'converted' => $converted,
                'active' => $active,
                'pending' => $pending,
            ];
            $data[$pid]['total'] += $total;
            $data[$pid]['converted'] += $converted;
            $data[$pid]['active'] += $active;
            $data[$pid]['pending'] += $pending;

            if (!isset($teamTotals[$tid])) {
                $teamTotals[$tid] = ['nom' => $row['team_nom'], 'total' => 0, 'converted' => 0, 'active' => 0, 'pending' => 0];
            }
            $teamTotals[$tid]['total'] += $total;
            $teamTotals[$tid]['converted'] += $converted;
            $teamTotals[$tid]['active'] += $active;
            $teamTotals[$tid]['pending'] += $pending;
        }

        uasort($teamTotals, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        // [team_id][user_id] => {username, nom, prenom, total, converted, active}
        $commercialsByTeam = [];
        foreach ($commercialRows as $row) {
            $tid = $row['team_id'];
            $uid = $row['user_id'];
            $total = (int) $row['total'];
            $converted = (int) $row['converted'];
            $commercialsByTeam[$tid][$uid] = [
                'username' => $row['user_username'],
                'nom' => $row['user_nom'],
                'prenom' => $row['user_prenom'],
                'total' => $total,
                'converted' => $converted,
                'active' => $total - $converted,
            ];
        }

        // [team_id][user_id][product_id] => {nom, total, converted}
        $commercialProdStats = [];
        foreach ($commercialProdRows as $row) {
            $commercialProdStats[$row['team_id']][$row['user_id']][$row['product_id']] = [
                'nom' => $row['product_nom'],
                'total' => (int) $row['total'],
                'converted' => (int) $row['converted'],
            ];
        }

        return $this->render('distribution/dashboard.html.twig', [
            'data' => $data,
            'teamTotals' => $teamTotals,
            'commercialsByTeam' => $commercialsByTeam,
            'commercialProdStats' => $commercialProdStats,
            'products' => $this->productRepository->findBy([], ['nom' => 'ASC']),
            'selectedProduct' => $selectedProduct,
            'from' => $fromRaw,
            'to' => $toRaw,
        ]);
    }

    /**
     * Configuration des quotas de distribution par équipe/produit — grille
     * éditable en une fois. Case vide = illimité, 0 = équipe exclue pour ce
     * produit, N = préférence de charge max (dépassée seulement si toutes
     * les équipes éligibles sont elles-mêmes à quota, voir
     * ProspectDistributionService::selectTeam()).
     */
    #[Route('/limits', name: 'app_distribution_limits', methods: ['GET', 'POST'])]
    public function limits(Request $request): Response
    {
        $teams = $this->teamRepository->findBy([], ['nom' => 'ASC']);
        $products = $this->productRepository->findBy([], ['nom' => 'ASC']);

        if ($request->isMethod('POST')) {
            $limits = $request->request->all('limits');

            foreach ($teams as $team) {
                foreach ($products as $product) {
                    $raw = $limits[$team->getId()][$product->getId()] ?? '';
                    $max = ($raw !== '' && $raw !== null) ? (int) $raw : null;

                    $entity = $this->limitRepository->findByTeamAndProduct($team, $product);

                    if ($max === null) {
                        if ($entity !== null) {
                            $this->entityManager->remove($entity);
                        }
                        continue;
                    }

                    if ($entity === null) {
                        $entity = (new TeamProductLimit())
                            ->setTeam($team)
                            ->setProduct($product);
                        $this->entityManager->persist($entity);
                    }
                    $entity->setMaxProspects(max($max, 0));
                }
            }

            $this->entityManager->flush();
            $this->addFlash('info', 'Limites de distribution enregistrées.');

            return $this->redirectToRoute('app_distribution_limits');
        }

        return $this->render('distribution/limits.html.twig', [
            'teams' => $teams,
            'products' => $products,
            'grid' => $this->limitRepository->findAllAsGrid(),
        ]);
    }
}
