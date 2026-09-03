<?php

namespace App\Tests\Controller;

use App\Entity\Client;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ContratControllerTest extends WebTestCase
{
    public function testIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/contrats');

        $this->assertResponseRedirects('/login');
    }

    public function testValiderTransitionsStatutAndAssignsNumeroPolice(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setUsername('user-contrat-test');
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'password123'));
        $em->persist($user);

        $clientEntity = (new Client())->setNom('ClientContratTest')->setPhone('0611112222');
        $product = (new Product())->setCode('valider-http-test')->setNom('Test HTTP');
        $em->persist($clientEntity);
        $em->persist($product);
        $em->flush();

        $contratService = $container->get(\App\Service\ContratService::class);
        $dto = new \App\Dto\ContratFormDTO();
        $dto->client = $clientEntity;
        $dto->product = $product;
        $contrat = $contratService->create($dto);

        $client->loginUser($user);
        $client->request('POST', '/contrats/'.$contrat->getId().'/valider');

        $this->assertResponseRedirects('/contrats/'.$contrat->getId());
        $client->followRedirect();
        $this->assertSelectorTextContains('body', 'Validé');
    }

    public function testDupliquerCreatesNewContratAndRedirectsToIt(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setUsername('user-contrat-dup-test');
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'password123'));
        $em->persist($user);

        $clientEntity = (new Client())->setNom('ClientDupTest')->setPhone('0611113333');
        $product = (new Product())->setCode('dup-http-test')->setNom('Test Dup');
        $em->persist($clientEntity);
        $em->persist($product);
        $em->flush();

        $contratService = $container->get(\App\Service\ContratService::class);
        $dto = new \App\Dto\ContratFormDTO();
        $dto->client = $clientEntity;
        $dto->product = $product;
        $contrat = $contratService->create($dto);

        $client->loginUser($user);
        $client->request('POST', '/contrats/'.$contrat->getId().'/dupliquer');

        $response = $client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertNotSame('/contrats/'.$contrat->getId(), $response->headers->get('Location'));
    }
}
