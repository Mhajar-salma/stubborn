<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginControllerTest extends WebTestCase
{
    public function testLogin(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        $repository = $em->getRepository(User::class);
        $user = $repository->findOneBy(['email' => 'email@example.com']);

        if (!$user) {
            $user = (new User())
                ->setEmail('email@example.com')
                ->setIsVerified(true);

            $hasher = $container->get('security.user_password_hasher');
            $user->setPassword($hasher->hashPassword($user, 'password'));

            $em->persist($user);
            $em->flush();
        }

        $crawler = $client->request('GET', '/login');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form();
        $form['_username'] = 'email@example.com';
        $form['_password'] = 'bad-password';

        $client->submit($form);
        self::assertResponseRedirects('/login');

        $crawler = $client->request('GET', '/login');
        $form = $crawler->filter('form')->form();
        $form['_username'] = 'email@example.com';
        $form['_password'] = 'password';

        $client->submit($form);
        self::assertResponseRedirects('/');
    }
}
