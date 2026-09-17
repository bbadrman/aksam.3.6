<?php

namespace App\Service;

use App\Entity\Prospect;
use App\Repository\ProspectRepository;

/**
 * Détecte les doublons email/téléphone parmi une liste de prospects déjà
 * chargée (un panier de traitement, un résultat de recherche...). Reprend
 * le principe de l'ancien `TraitementController::detectDuplicatesOptimized()` :
 * une requête groupée par critère plutôt qu'une requête par prospect.
 */
class ProspectDuplicateService
{
    public function __construct(
        private readonly ProspectRepository $prospectRepository,
    ) {
    }

    /**
     * @param iterable<Prospect> $prospects
     * @return array{emails: array<string, bool>, phones: array<string, bool>}
     */
    public function detect(iterable $prospects): array
    {
        $duplicates = ['emails' => [], 'phones' => []];

        $emails = [];
        $phoneVariants = [];

        foreach ($prospects as $prospect) {
            if ($email = $prospect->getEmail()) {
                $emails[] = $email;
            }

            if ($phone = $prospect->getPhone()) {
                $normalized = $this->normalizePhone($phone);
                if ($normalized !== null) {
                    foreach ($this->phoneVariants($normalized) as $variant) {
                        $phoneVariants[$variant] = true;
                    }
                }
            }
        }

        $emails = array_values(array_unique($emails));
        $phoneVariants = array_keys($phoneVariants);

        foreach ($this->prospectRepository->findDuplicateEmails($emails) as $email) {
            if ($email !== null) {
                $duplicates['emails'][$email] = true;
            }
        }

        $rawPhonesByNormalized = [];
        foreach ($this->prospectRepository->findPhonesMatchingVariants($phoneVariants) as $rawPhone) {
            $normalized = $this->normalizePhone($rawPhone);
            if ($normalized !== null) {
                $rawPhonesByNormalized[$normalized][] = $rawPhone;
            }
        }

        foreach ($rawPhonesByNormalized as $rawPhones) {
            if (\count($rawPhones) > 1) {
                foreach ($rawPhones as $rawPhone) {
                    $duplicates['phones'][$rawPhone] = true;
                }
            }
        }

        return $duplicates;
    }

    /**
     * Normalise un numéro français vers sa forme locale (0XXXXXXXXX) afin de
     * comparer "0611112224" et "+33611112224" comme identiques.
     */
    private function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $p = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($p, '+33')) {
            $p = '0' . substr($p, 3);
        } elseif (str_starts_with($p, '0033')) {
            $p = '0' . substr($p, 4);
        } elseif (str_starts_with($p, '33') && \strlen($p) === 11) {
            $p = '0' . substr($p, 2);
        }

        return $p ?: null;
    }

    /**
     * @return string[]
     */
    private function phoneVariants(string $normalized): array
    {
        $variants = [$normalized];

        if (str_starts_with($normalized, '0')) {
            $suffix = substr($normalized, 1);
            $variants[] = '+33' . $suffix;
            $variants[] = '0033' . $suffix;
            $variants[] = '33' . $suffix;
        }

        return $variants;
    }
}
