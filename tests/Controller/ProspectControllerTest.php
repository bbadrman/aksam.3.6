<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\Prospect;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProspectControllerTest extends WebTestCase
{
    public function testCycleScreensRequireAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/traitement/nouveaux');

        $this->assertResponseRedirects('/login');
    }

    public function testNouveauxScreenListsUnassignedProspects(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = $this->createLoggedInUser($client, 'user-cycle-nouveaux', ['ROLE_ADMIN']);

        $product = (new Product())->setCode('test-cycle')->setNom('Test Cycle');
        $prospect = (new Prospect())->setNom('VisibleDansNouveaux')->setPhone('0655555555')->setProduct($product);

        $em->persist($product);
        $em->persist($prospect);
        $em->flush();

        $client->request('GET', '/traitement/nouveaux');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'VisibleDansNouveaux');
    }

    public function testTraiterActionUpdatesProspectAndRedirects(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $this->createLoggedInUser($client, 'user-traiter', ['ROLE_ADMIN']);

        $product = (new Product())->setCode('test-traiter')->setNom('Test Traiter');
        $prospect = (new Prospect())->setNom('AtraiterViaHttp')->setPhone('0666666666')->setProduct($product);

        $em->persist($product);
        $em->persist($prospect);
        $em->flush();

        $client->request('POST', '/traitement/'.$prospect->getId().'/traiter', [
            'motif' => '1', // RENDEZ_VOUS
            'comment' => 'RDV confirmé',
            'retour' => 'app_traitement_nouveaux',
        ]);

        $this->assertResponseRedirects('/traitement/nouveaux');

        $em->refresh($prospect);
        $this->assertSame(\App\Entity\RelanceMotif::RENDEZ_VOUS, $prospect->getRelance());
    }

    private function createLoggedInUser($client, string $username, array $roles): User
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setUsername($username);
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setRoles($roles);
        $user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();

        $client->loginUser($user);

        return $user;
    }
}
