<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Prospect;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;

class ClientService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClientRepository $clientRepository,
    ) {
    }

    /**
     * Convertit un Prospect qualifié en Client. Le Prospect garde son
     * historique (statut passé à "converti") ; le Client est la nouvelle
     * source unique de vérité pour l'identité — nom/prénom ne doivent plus
     * jamais être dupliqués ailleurs à partir d'ici (cf. Contrat, étape 5).
     */
    public function convertFromProspect(Prospect $prospect): Client
    {
        // On interroge le repository plutôt que $prospect->getClient() : côté
        // inverse d'une OneToOne, cette association n'est pas rafraîchie en
        // mémoire après un flush() dans la même unité de travail.
        if ($this->clientRepository->findOneBy(['prospect' => $prospect]) !== null) {
            throw new \RuntimeException(sprintf('Le prospect #%d a déjà été converti en client.', $prospect->getId()));
        }

        $client = new Client();
        $client->setNom($prospect->getNom());
        $client->setPrenom($prospect->getPrenom());
        $client->setPhone($prospect->getPhone());
        $client->setEmail($prospect->getEmail());
        $client->setTeam($prospect->getTeam());
        $client->setCommercial($prospect->getCommercial());
        $client->setProspect($prospect);

        $prospect->setStatut(Prospect::STATUT_CONVERTI);

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        return $client;
    }
}
