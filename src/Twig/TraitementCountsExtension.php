<?php

namespace App\Twig;

use App\Service\ProspectTraitementService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Expose les compteurs des cycles de traitement au sidebar (affiché sur
 * toutes les pages) sans dupliquer les appels au service dans chaque
 * contrôleur.
 */
class TraitementCountsExtension extends AbstractExtension
{
    private ?array $counts = null;

    public function __construct(
        private readonly ProspectTraitementService $traitementService,
        private readonly Security $security,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('traitement_count', $this->getCount(...)),
        ];
    }

    public function getCount(string $cycle): int
    {
        if (null === $this->counts) {
            $this->counts = $this->security->isGranted('ROLE_USER') ? [
                'nouveaux' => $this->traitementService->nouveaux()['total'],
                'non_traites' => $this->traitementService->nonTraites()['total'],
                'relances_du_jour' => $this->traitementService->relancesDuJour()['total'],
                'relances_a_venir' => $this->traitementService->relancesAVenir()['total'],
                'relances_non_traitees' => $this->traitementService->relancesNonTraitees()['total'],
                'injoignables' => $this->traitementService->injoignables()['total'],
            ] : [];
        }

        return $this->counts[$cycle] ?? 0;
    }
}
