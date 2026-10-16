<?php

namespace App\Tests\Controller;

use App\Entity\Client;
use App\Entity\Product;
use App\Entity\Prospect;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ClientControllerTest extends WebTestCase
{
    public function testConvertFromProspectRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/clients/depuis-prospect/1');

        $this->assertResponseRedirects('/login');
    }

    public function testConvertFromProspectCreatesClientAndRedirects(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setUsername('user-client-test');
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'password123'));
        $em->persist($user);

        $product = (new Product())->setCode('test-conv')->setNom('Produit Conv');
        $em->persist($product);

        $prospect = (new Prospect())->setNom('Convertible')->setPhone('0644444444')->setProduct($product);
        $em->persist($prospect);
        $em->flush();

        $client->loginUser($user);
        $client->request('POST', '/clients/depuis-prospect/'.$prospect->getId());

        $this->assertResponseRedirects('/clients');
        $client->followRedirect();
        $this->assertSelectorTextContains('body', 'Convertible');
    }

    public function testConvertUnknownProspectReturns404(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setUsername('user-client-test-2');
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();

        $client->loginUser($user);
        $client->request('POST', '/clients/depuis-prospect/999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowDisplaysPreClientAndValidates(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setUsername('user-client-show');
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'password123'));
        $em->persist($user);

        $fiche = (new Client())->setNom('Dupont')->setPrenom('Jean')->setPhone('0655555555')->setAdresse('1 rue de Paris');
        $em->persist($fiche);
        $em->flush();

        $client->loginUser($user);
        $crawler = $client->request('GET', '/clients/'.$fiche->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Pré-client');
        $this->assertSelectorTextContains('body', '1 rue de Paris');
        $this->assertCount(3, $crawler->filter('button[name="client_status[status]"]'));

        $form = $crawler->selectButton('Valider')->form();
        $client->submit($form, ['client_status[status]' => (string) Client::STATUS_VALIDE]);

        $this->assertResponseRedirects('/clients/'.$fiche->getId());
        $crawler = $client->followRedirect();
        $this->assertSelectorTextContains('body', 'Validé');
        $this->assertCount(0, $crawler->filter('button[name="client_status[status]"]'));

        $em->clear();
        $this->assertSame(Client::STATUS_VALIDE, $em->find(Client::class, $fiche->getId())->getStatus());
    }
}
