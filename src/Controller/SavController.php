<?php

namespace App\Controller;

use App\Repository\ContratRepository;
use App\Repository\SavRepository;
use App\Security\SavVoter;
use App\Service\SavService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/savs', name: 'app_sav_')]
class SavController extends AbstractController
{
    public function __construct(private readonly SavService $savService)
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(SavRepository $savRepository): Response
    {
        return $this->render('sav/index.html.twig', [
            'savs' => $savRepository->findBy([], ['createdAt' => 'DESC'], 50),
        ]);
    }

    #[Route('/nouveau/{contratId}', name: 'new', methods: ['GET', 'POST'], requirements: ['contratId' => '\d+'])]
    public function new(int $contratId, Request $request, ContratRepository $contratRepository): Response
    {
        $contrat = $contratRepository->find($contratId);
        if (!$contrat) {
            throw $this->createNotFoundException('Contrat introuvable.');
        }

        if ($request->isMethod('POST')) {
            $sav = $this->savService->ouvrir(
                $contrat,
                $request->request->get('natureDemande'),
                $request->request->get('motif'),
                $request->request->get('montant') ?: null,
            );

            return $this->redirectToRoute('app_sav_show', ['id' => $sav->getId()]);
        }

        return $this->render('sav/new.html.twig', ['contrat' => $contrat]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, SavRepository $savRepository): Response
    {
        $sav = $savRepository->find($id);
        if (!$sav) {
            throw $this->createNotFoundException('SAV introuvable.');
        }

        return $this->render('sav/show.html.twig', [
            'sav' => $sav,
            'peutCloturer' => $this->isGranted(SavVoter::CLOTURER, $sav),
        ]);
    }

    #[Route('/{id}/relancer', name: 'relancer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function relancer(int $id, Request $request, SavRepository $savRepository): Response
    {
        $sav = $savRepository->find($id);
        if (!$sav) {
            throw $this->createNotFoundException('SAV introuvable.');
        }

        $prochaineRelanceStr = $request->request->get('prochaineRelance');
        $prochaineRelance = $prochaineRelanceStr ? new \DateTimeImmutable($prochaineRelanceStr) : null;

        try {
            $this->savService->ajouterRelance($sav, $request->request->get('comment'), $prochaineRelance, $this->getUser());
            $this->addFlash('info', 'Relance enregistrée.');
        } catch (\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_sav_show', ['id' => $sav->getId()]);
    }

    #[Route('/{id}/cloturer', name: 'cloturer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cloturer(int $id, SavRepository $savRepository): Response
    {
        $sav = $savRepository->find($id);
        if (!$sav) {
            throw $this->createNotFoundException('SAV introuvable.');
        }

        $this->denyAccessUnlessGranted(SavVoter::CLOTURER, $sav);

        try {
            $this->savService->cloturer($sav);
            $this->addFlash('info', 'SAV clôturé.');
        } catch (\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_sav_show', ['id' => $sav->getId()]);
    }
}
