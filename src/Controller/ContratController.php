<?php

namespace App\Controller;

use App\Dto\ContratFormDTO;
use App\Form\ContratType;
use App\Repository\ContratRepository;
use App\Service\ContratService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/contrats', name: 'app_contrat_')]
class ContratController extends AbstractController
{
    public function __construct(private readonly ContratService $contratService)
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ContratRepository $contratRepository): Response
    {
        return $this->render('contrat/index.html.twig', [
            'contrats' => $contratRepository->findBy([], ['createdAt' => 'DESC'], 50),
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $dto = new ContratFormDTO();
        $form = $this->createForm(ContratType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contrat = $this->contratService->create($dto);
            $this->addFlash('info', 'Contrat créé en brouillon.');

            return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
        }

        return $this->render('contrat/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, ContratRepository $contratRepository): Response
    {
        $contrat = $contratRepository->find($id);
        if (!$contrat) {
            throw $this->createNotFoundException('Contrat introuvable.');
        }

        return $this->render('contrat/show.html.twig', ['contrat' => $contrat]);
    }

    #[Route('/{id}/valider', name: 'valider', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function valider(int $id, ContratRepository $contratRepository): Response
    {
        $contrat = $contratRepository->find($id);
        if (!$contrat) {
            throw $this->createNotFoundException('Contrat introuvable.');
        }

        try {
            $this->contratService->valider($contrat);
            $this->addFlash('info', sprintf('Contrat validé sous le numéro %s.', $contrat->getNumeroPolice()));
        } catch (\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
    }

    #[Route('/{id}/dupliquer', name: 'dupliquer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function dupliquer(int $id, ContratRepository $contratRepository): Response
    {
        $source = $contratRepository->find($id);
        if (!$source) {
            throw $this->createNotFoundException('Contrat introuvable.');
        }

        $copie = $this->contratService->duplicate($source);
        $this->addFlash('info', sprintf('Contrat #%d dupliqué en brouillon.', $source->getId()));

        return $this->redirectToRoute('app_contrat_show', ['id' => $copie->getId()]);
    }
}
