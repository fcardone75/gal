<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    private ?EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $container = $this->client->getContainer();


        // Verify other dependencies
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->passwordHasher = $container->get('security.password_hasher');


        // Ensure the test user does not exist before the tests
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'test@example.com']);
        if ($user) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }
    }

    public function testMfa(): void
    {
        $mfaEnabled = $_ENV['MFA_ENABLED'];

        // Create a new user
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        // Set other user properties if required
//        $user->setRoles(["ROLE_OPERATORE_CONFIDI", "ROLE_USER"]);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Make a POST request to the /login route with user's credentials
        $this->client->request('POST', '/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->client->followRedirect();
        $response = $this->client->getResponse();
        if ($mfaEnabled=='true') {
        $this->assertStringContainsString('Enable 2FA', $response->getContent());
        } else {
            $this->assertStringNotContainsString('Enable 2FA', $response->getContent());
        }
        $this->assertEquals(200, $response->getStatusCode());

    }

    protected function tearDown(): void
    {
        // Clean up the database after the test
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'test@example.com']);
        if ($user) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }

        $this->entityManager->close();
        $this->entityManager = null;

        parent::tearDown();
    }
}
