<?php

namespace App\Service;

use App\Entity\Contrat;
use App\Entity\Sav;
use App\Entity\SavRelanceHistory;
use App\Entity\SavStatus;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Porte les transitions d'état d'un Sav — logique qui vivait directement
 * (et partiellement) dans SavController::afficher() dans l'ancien projet.
 */
class SavService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function ouvrir(Contrat $contrat, ?string $natureDemande, ?string $motif, ?string $montant): Sav
    {
        $sav = new Sav();
        $sav->setContrat($contrat);
        $sav->setNatureDemande($natureDemande);
        $sav->setMotif($motif);
        $sav->setMontant($montant);

        $this->entityManager->persist($sav);
        $this->entityManager->flush();

        return $sav;
    }

    /**
     * Enregistre une relance : trace l'historique et passe le SAV en
     * "traité" s'il était encore "ouvert". Un SAV déjà clôturé ne peut
     * plus être relancé.
     */
    public function ajouterRelance(Sav $sav, ?string $comment, ?\DateTimeImmutable $prochaineRelance, User $auteur): void
    {
        if ($sav->getStatut() === SavStatus::CLOTURE) {
            throw new \RuntimeException(sprintf('Le SAV #%d est clôturé, il ne peut plus être relancé.', $sav->getId()));
        }

        $entry = (new SavRelanceHistory())
            ->setComment($comment)
            ->setAuteur($auteur);

        $sav->addRelanceHistory($entry);
        $sav->setRelanceAt($prochaineRelance);

        if ($sav->getStatut() === SavStatus::OUVERT) {
            $sav->setStatut(SavStatus::TRAITE);
        }

        $this->entityManager->persist($sav);
        $this->entityManager->flush();
    }

    /**
     * Clôture un SAV. Refuse de clôturer un SAV déjà clôturé (idempotence
     * explicite, plutôt qu'un flush silencieux sans effet).
     */
    public function cloturer(Sav $sav): void
    {
        if ($sav->getStatut() === SavStatus::CLOTURE) {
            throw new \RuntimeException(sprintf('Le SAV #%d est déjà clôturé.', $sav->getId()));
        }

        $sav->setStatut(SavStatus::CLOTURE);
        $sav->setClosedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }
}
