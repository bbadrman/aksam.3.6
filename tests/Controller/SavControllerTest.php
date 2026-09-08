<?php

namespace App\Tests\Controller;

use App\Entity\Client;
use App\Entity\Contrat;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SavControllerTest extends WebTestCase
{
    public function testIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/savs');

        $this->assertResponseRedirects('/login');
    }

    public function testOpenRelanceAndShowFlow(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = $this->createLoggedInUser($client, 'user-sav-http', ['ROLE_ADMIN']);
        $contrat = $this->createContrat($em, 'sav-http-test');

        // Ouverture via le formulaire HTTP.
        $client->request('POST', '/savs/nouveau/'.$contrat->getId(), [
            'natureDemande' => 'Réclamation',
            'motif' => 'Facture incorrecte',
        ]);

        $response = $client->getResponse();
        $this->assertTrue($response->isRedirect());
        preg_match('#/savs/(\d+)#', $response->headers->get('Location'), $matches);
        $savId = (int) $matches[1];

        $client->request('GET', '/savs/'.$savId);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Réclamation');

        // Relance.
        $client->request('POST', '/savs/'.$savId.'/relancer', ['comment' => 'Contact effectué']);
        $this->assertResponseRedirects('/savs/'.$savId);
        $client->followRedirect();
        $this->assertSelectorTextContains('body', 'Contact effectué');
        $this->assertSelectorTextContains('body', 'Traité');

        // Clôture (admin autorisé par le SavVoter).
        $client->request('POST', '/savs/'.$savId.'/cloturer');
        $this->assertResponseRedirects('/savs/'.$savId);
        $client->followRedirect();
        $this->assertSelectorTextContains('body', 'Clôturé');
    }

    public function testNonAdminNonGestionnaireCannotCloturer(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $this->createLoggedInUser($client, 'user-sav-simple', []);
        $contrat = $this->createContrat($em, 'sav-http-voter-test');

        $client->request('POST', '/savs/nouveau/'.$contrat->getId(), ['natureDemande' => 'Test']);
        $location = $client->getResponse()->headers->get('Location');
        preg_match('#/savs/(\d+)#', $location, $matches);
        $savId = (int) $matches[1];

        $client->request('POST', '/savs/'.$savId.'/cloturer');
        $this->assertResponseStatusCodeSame(403);
    }

    private function createContrat(EntityManagerInterface $em, string $productCode): Contrat
    {
        $clientEntity = (new Client())->setNom('Client'.$productCode)->setPhone('0600000000');
        $product = (new Product())->setCode($productCode)->setNom('Produit '.$productCode);
        $contrat = (new Contrat())->setClient($clientEntity)->setProduct($product);

        $em->persist($clientEntity);
        $em->persist($product);
        $em->persist($contrat);
        $em->flush();

        return $contrat;
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
