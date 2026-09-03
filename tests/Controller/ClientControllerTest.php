<?php

namespace App\Tests\Controller;

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
}
