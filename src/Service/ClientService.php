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
     *
     * $phone/$email/$raisonSociale/$adresse permettent de surcharger les
     * coordonnées du prospect au moment de la conversion (cas "Passage
     * client" saisi depuis le traitement, où le commercial peut avoir des
     * coordonnées plus à jour que celles du prospect).
     */
    public function convertFromProspect(
        Prospect $prospect,
        ?string $phone = null,
        ?string $email = null,
        ?string $raisonSociale = null,
        ?string $adresse = null,
    ): Client {
        // On interroge le repository plutôt que $prospect->getClient() : côté
        // inverse d'une OneToOne, cette association n'est pas rafraîchie en
        // mémoire après un flush() dans la même unité de travail.
        if ($this->clientRepository->findOneBy(['prospect' => $prospect]) !== null) {
            throw new \RuntimeException(sprintf('Le prospect #%d a déjà été converti en client.', $prospect->getId()));
        }

        $client = new Client();
        $client->setNom($prospect->getNom());
        $client->setPrenom($prospect->getPrenom());
        $client->setPhone($phone ?: ($prospect->getPhone() ?? ''));
        $client->setEmail($email ?: $prospect->getEmail());
        $client->setRaisonSociale($raisonSociale);
        $client->setAdresse($adresse);
        $client->setTeam($prospect->getTeam());
        $client->setCommercial($prospect->getCommercial());
        $client->setProspect($prospect);

        $prospect->setStatut(Prospect::STATUT_CONVERTI);

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        return $client;
    }

    /**
     * Décision finale sur un pré-client. Une seule décision possible :
     * un client déjà validé/rejeté/annulé ne peut plus changer de statut.
     */
    public function decider(Client $client, int $status): void
    {
        if (!$client->isPreClient()) {
            throw new \RuntimeException(sprintf('Ce client a déjà été traité (%s).', $client->getStatusLabel()));
        }

        $client->setStatus($status);
        $this->entityManager->flush();
    }
}
