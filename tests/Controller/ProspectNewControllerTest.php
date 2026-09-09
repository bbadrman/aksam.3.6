<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProspectNewControllerTest extends WebTestCase
{
    public function testNewRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/traitement/nouveau');

        $this->assertResponseRedirects('/login');
    }

    public function testSubmittingFormCreatesProspectAndRedirectsToShow(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setUsername('user-prospect-new-test');
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'password123'));
        $em->persist($user);

        $product = (new Product())->setCode('test-prospect-new')->setNom('Test Prospect New');
        $em->persist($product);
        $em->flush();

        $client->loginUser($user);

        $crawler = $client->request('GET', '/traitement/nouveau');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer le prospect')->form([
            'prospect[nom]' => 'FormulaireTest',
            'prospect[phone]' => '0699999999',
            'prospect[product]' => (string) $product->getId(),
        ]);
        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
        $this->assertSelectorTextContains('body', 'FormulaireTest');
    }
}
