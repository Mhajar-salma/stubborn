<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccountControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        $user = $em->getRepository(User::class)->findOneBy([
            'email' => 'account@example.com',
        ]);

        if (!$user) {
            $user = (new User())
                ->setEmail('account@example.com')
                ->setIsVerified(true);

            $hasher = $container->get('security.user_password_hasher');
            $user->setPassword($hasher->hashPassword($user, 'password'));

            $em->persist($user);
            $em->flush();
        }

        $client->loginUser($user);
        $client->request('GET', '/account');

        self::assertResponseIsSuccessful();
    }
}
