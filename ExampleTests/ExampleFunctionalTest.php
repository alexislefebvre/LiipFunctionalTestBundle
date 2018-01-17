<?php

declare(strict_types=1);

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FooBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @author Lukas Smith
 * @author Daniel Barsotti
 * @author Albert Jessurum
 */
class ExampleFunctionalTest extends WebTestCase
{
    /**
     * Example using LiipFunctionalBundle the fixture loader.
     */
    public function testUserFooIndex(): void
    {
        $this->loadFixtures(['Liip\FooBundle\Tests\Fixtures\LoadUserData']);

        $client = $this->createClient();
        $crawler = $client->request('GET', '/users/foo');
        $this->assertStatusCode(200, $client);

        $this->assertTrue($crawler->filter('html:contains("Email: foo@bar.com")')->count() > 0);
    }

    /**
     * Example using LiipFunctionalBundle WebTestCase helpers and with authentication.
     */
    public function testBasicAuthentication(): void
    {
        $this->loadFixtures(['Liip\FooBundle\Tests\Fixtures\LoadUserData']);

        $content = $this->fetchContent('/users/foo', 'GET', true);
        $this->assertSame('Hello foo!', $content);

        // check if the logout button is shown
        $this->assertContains('logout', $content);
    }

    public function test404Page(): void
    {
        $this->fetchContent('/asdasdas', 'GET', false, false);
    }

    public function testLoginPage(): void
    {
        $content = $this->fetchContent('/', 'GET', false);
        $this->assertContains('login', $content);
    }

    public function testValidationErrors(): void
    {
        $client = $this->makeClient(true);
        $crawler = $client->request('GET', '/users/1/edit');

        $client->submit($crawler->selectButton('Save')->form());

        $this->assertValidationErrors(['data.username', 'data.email'], $client->getContainer());
    }
}
