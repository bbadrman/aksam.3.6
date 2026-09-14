<?php

namespace App\Tests\Controller;

use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProspectTeamsCommerciauxApiTest extends WebTestCase
{
    public function testEndpointRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/traitement/api/equipes-commerciaux');

        $this->assertResponseRedirects('/login');
    }

    public function testEndpointReturnsTeamsWithOnlyActiveCommercials(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setUsername('user-teams-api-test');
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'password123'));

        $team = (new Team())->setNom('Équipe API Test');

        $commercialActif = new User();
        $commercialActif->setUsername('commercial-actif-test');
        $commercialActif->setNom('Actif');
        $commercialActif->setPrenom('Commercial');
        $commercialActif->setPassword('hash-non-utilise');
        $commercialActif->setActif(true);
        $commercialActif->addTeam($team);

        $commercialInactif = new User();
        $commercialInactif->setUsername('commercial-inactif-test');
        $commercialInactif->setNom('Inactif');
        $commercialInactif->setPrenom('Commercial');
        $commercialInactif->setPassword('hash-non-utilise');
        $commercialInactif->setActif(false);
        $commercialInactif->addTeam($team);

        $em->persist($user);
        $em->persist($team);
        $em->persist($commercialActif);
        $em->persist($commercialInactif);
        $em->flush();
        $teamId = $team->getId();

        // Sans ce clear(), l'identity map Doctrine garderait en mémoire la
        // collection $team->users vide (chargée avant que les relations
        // ManyToMany ne soient flushées), et la fausserait pour la requête
        // HTTP qui suit dans ce même test.
        $em->clear();

        $client->loginUser($user);
        $client->request('GET', '/traitement/api/equipes-commerciaux');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $apiTeam = current(array_filter($data, fn (array $t) => $t['id'] === $teamId));
        $this->assertNotFalse($apiTeam);

        $usernames = array_column($apiTeam['commercials'], 'username');
        $this->assertContains('commercial-actif-test', $usernames);
        $this->assertNotContains('commercial-inactif-test', $usernames);
    }
}
