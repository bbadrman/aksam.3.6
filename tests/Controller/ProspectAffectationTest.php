<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\Prospect;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProspectAffectationTest extends WebTestCase
{
    public function testAffecterRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/traitement/1/affecter');

        $this->assertResponseRedirects('/login');
    }

    public function testAffecterSetsTeamAndCommercialAndAffectAt(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setUsername('user-affectation-test');
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'password123'));

        $commercial = new User();
        $commercial->setUsername('commercial-affectation-test');
        $commercial->setNom('Commercial');
        $commercial->setPrenom('Test');
        $commercial->setPassword('hash-non-utilise');

        $team = (new Team())->setNom('Équipe Affectation Test');
        $product = (new Product())->setCode('affectation-test')->setNom('Test Affectation');
        $prospect = (new Prospect())->setNom('AAffecter')->setPhone('0600000000')->setProduct($product);

        $em->persist($user);
        $em->persist($commercial);
        $em->persist($team);
        $em->persist($product);
        $em->persist($prospect);
        $em->flush();

        $client->loginUser($user);
        $client->request('POST', '/traitement/'.$prospect->getId().'/affecter', [
            'team' => (string) $team->getId(),
            'commercial' => (string) $commercial->getId(),
        ]);

        $this->assertResponseRedirects('/traitement/'.$prospect->getId());

        $em->refresh($prospect);
        $this->assertSame($team->getId(), $prospect->getTeam()->getId());
        $this->assertSame($commercial->getId(), $prospect->getCommercial()->getId());
        $this->assertNotNull($prospect->getAffectAt());
    }

    public function testAffecterWithEmptySelectionsClearsAffectation(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setUsername('user-desaffectation-test');
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'password123'));

        $team = (new Team())->setNom('Équipe Desaffectation Test');
        $product = (new Product())->setCode('desaffectation-test')->setNom('Test');
        $prospect = (new Prospect())->setNom('DejaAffecte')->setPhone('0600000001')->setProduct($product);
        $prospect->setTeam($team);
        $prospect->setAffectAt(new \DateTimeImmutable());

        $em->persist($user);
        $em->persist($team);
        $em->persist($product);
        $em->persist($prospect);
        $em->flush();

        $client->loginUser($user);
        $client->request('POST', '/traitement/'.$prospect->getId().'/affecter', [
            'team' => '',
            'commercial' => '',
        ]);

        $this->assertResponseRedirects('/traitement/'.$prospect->getId());

        $em->refresh($prospect);
        $this->assertNull($prospect->getTeam());
        $this->assertNull($prospect->getCommercial());
        $this->assertNull($prospect->getAffectAt());
    }
}
