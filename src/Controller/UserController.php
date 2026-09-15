<?php

namespace App\Controller;

use App\Dto\UserFormDTO;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Security\Permission;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/utilisateurs', name: 'app_user_')]
class UserController extends AbstractController
{
    public function __construct(private readonly UserService $userService)
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $dto = new UserFormDTO();
        $dto->roles = ['ROLE_USER'];
        foreach (Permission::cases() as $permission) {
            if ($permission->defaultGranted()) {
                $dto->permissions[] = $permission->value;
            }
        }
        $form = $this->createForm(UserType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->userService->create($dto);
            $this->addFlash('info', sprintf('Utilisateur "%s" créé.', $user->getUsername()));

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/editer', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $dto = new UserFormDTO();
        $dto->username = $user->getUsername();
        $dto->nom = $user->getNom();
        $dto->prenom = $user->getPrenom();
        $dto->actif = $user->isActif();
        $dto->roles = $user->getRoles();
        foreach (Permission::cases() as $permission) {
            if ($user->hasPermission($permission)) {
                $dto->permissions[] = $permission->value;
            }
        }
        foreach ($user->getTeams() as $team) {
            $dto->teams->add($team);
        }
        foreach ($user->getProducts() as $product) {
            $dto->products->add($product);
        }

        $form = $this->createForm(UserType::class, $dto, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userService->update($user, $dto);
            $this->addFlash('info', sprintf('Utilisateur "%s" mis à jour.', $user->getUsername()));

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/edit.html.twig', ['form' => $form, 'user' => $user]);
    }
}
