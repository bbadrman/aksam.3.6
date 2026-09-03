<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    public function testHomeRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseRedirects('/login');
    }

    public function testLoginWithValidCredentials(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $user = new User();
        $user->setUsername('test-login');
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword(
            $container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'password123')
        );

        $em = $container->get(EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            'username' => 'test-login',
            'password' => 'password123',
        ]);
        $this->submitWithStatelessCsrf($client, $form);

        $this->assertResponseRedirects('/');
        $client->followRedirect();
        $this->assertSelectorTextContains('body', 'test-login');
    }

    public function testLoginWithInvalidCredentialsShowsError(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            'username' => 'unknown-user',
            'password' => 'wrongpassword',
        ]);
        $this->submitWithStatelessCsrf($client, $form);

        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }

    /**
     * Le formulaire de login utilise la protection CSRF "stateless" de Symfony
     * (double-submit cookie), normalement générée en JS par
     * assets/controllers/csrf_protection_controller.js. BrowserKit n'exécute pas
     * de JS : on reproduit ici manuellement la même logique cookie+champ.
     */
    private function submitWithStatelessCsrf($client, $form): void
    {
        $cookieName = $form->get('_csrf_token')->getValue(); // valeur placeholder, ex: "csrf-token"
        $token = base64_encode(random_bytes(18));
        $form->get('_csrf_token')->setValue($token);

        $client->getCookieJar()->set(new Cookie($cookieName.'_'.$token, $cookieName));
        $client->submit($form);
    }
}
