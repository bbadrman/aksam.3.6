<?php

namespace App\Tests\Service;

use App\Entity\Product;
use App\Entity\Prospect;
use App\Entity\RelanceMotif;
use App\Entity\Team;
use App\Entity\User;
use App\Service\ProspectTraitementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ProspectTraitementServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private Product $product;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->product = (new Product())->setCode('test-traitement')->setNom('Test');
        $this->em->persist($this->product);
    }

    public function testEnregistrerRelanceUpdatesStateAndHistory(): void
    {
        $auteur = $this->createUser('auteur-relance');
        $prospect = $this->createProspect('Ferrand');

        $this->em->persist($auteur);
        $this->em->persist($prospect);
        $this->em->flush();

        $service = static::getContainer()->get(ProspectTraitementService::class);
        $prochaine = new \DateTimeImmutable('+3 days');
        $service->enregistrerRelance($prospect, RelanceMotif::RENDEZ_VOUS, 'RDV pris', $prochaine, $auteur);

        $this->assertSame(RelanceMotif::RENDEZ_VOUS, $prospect->getRelance());
        $this->assertEquals($prochaine, $prospect->getRelanceAt());
        $this->assertCount(1, $prospect->getRelanceHistory());
        $this->assertSame('RDV pris', $prospect->getRelanceHistory()->first()->getComment());
    }

    public function testNouveauxExcludesProspectsAlreadyAffected(): void
    {
        $nouveau = $this->createProspect('Nouveau');
        $affecte = $this->createProspect('Affecte');
        $affecte->setTeam((new Team())->setNom('Équipe Scope Test'));

        $this->em->persist($nouveau);
        $this->em->persist($affecte->getTeam());
        $this->em->persist($affecte);
        $this->em->flush();

        $this->loginAsAdmin();
        $resultats = static::getContainer()->get(ProspectTraitementService::class)->nouveaux();
        $noms = array_map(fn (Prospect $p) => $p->getNom(), $resultats['items']);

        $this->assertContains('Nouveau', $noms);
        $this->assertNotContains('Affecte', $noms);
    }

    private function createProspect(string $nom): Prospect
    {
        return (new Prospect())->setNom($nom)->setPhone('0600000000')->setProduct($this->product);
    }

    private function createUser(string $username): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setPassword('hash-non-utilise');

        return $user;
    }

    private function loginAsAdmin(): void
    {
        $admin = $this->createUser('admin-scope-test');
        $admin->setRoles(['ROLE_ADMIN']);
        $this->em->persist($admin);
        $this->em->flush();

        $token = new UsernamePasswordToken($admin, 'main', $admin->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
