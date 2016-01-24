<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Tests\Test;

use Liip\FunctionalTestBundle\Annotations\QueryCount;
use Liip\FunctionalTestBundle\Test\WebTestCase;

/**
 * Tests that configuration has been loaded and users can be logged in.
 *
 * Use Tests/App/Config/AppConfigKernel.php instead of
 * Tests/App/AppKernel.php.
 * So it must be loaded in a separate process.
 *
 * @runTestsInSeparateProcesses
 */
class WebTestCaseConfigTest extends WebTestCase
{
    private $client = null;

    protected static function getKernelClass()
    {
        require_once __DIR__.'/../App/Config/AppConfigKernel.php';

        return 'AppConfigKernel';
    }

    /**
     * Log in as an user.
     */
    public function testIndexAuthenticationArray()
    {
        $this->loadFixtures(array());

        $this->client = static::makeClient(array(
            'username' => 'foobar',
            'password' => '12341234',
        ));

        $path = '/';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'Logged in as foobar.',
            $crawler->filter('p#user')->text()
        );

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );
    }

    /**
     * Log in as the user defined in the
     * "liip_functional_test.authentication"
     * node from the configuration file.
     */
    public function testIndexAuthenticationTrue()
    {
        $this->loadFixtures(array());

        $this->client = static::makeClient(true);

        $path = '/';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'Logged in as foobar.',
            $crawler->filter('p#user')->text()
        );

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );
    }

    /**
     * Log in as the user defined in the
     * "liip_functional_test.authentication"
     * node from the configuration file.
     */
    public function testAdminAuthenticationTrue()
    {
        $this->loadFixtures(array());

        $this->client = static::makeClient(true);

        $path = '/admin';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(403, $this->client);

        $this->isSuccessful($this->client->getResponse(), false);
    }

    /**
     * Log in as the admin defined in the
     * "security.providers.in_memory"
     * node from the configuration file.
     */
    public function testAdminAuthenticationInMemoryRoleAdmin()
    {
        $this->loadFixtures(array());

        $this->client = static::makeClient(array(
            'username' => 'roleadmin',
            'password' => '12341234',
        ));

        $path = '/admin';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        $this->isSuccessful($this->client->getResponse());

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'Logged in as roleadmin.',
            $crawler->filter('p#user')->text()
        );

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );
    }

    /**
     * Log in as the user defined in the Data Fixtures.
     *
     * @QueryCount(1)
     */
    public function testAdminAuthenticationLoginAs()
    {
        $fixtures = $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\DataFixtures\ORM\LoadUserData',
        ));

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\AbstractExecutor',
            $fixtures
        );

        $repository = $fixtures->getReferenceRepository();

        $loginAs = $this->loginAs($repository->getReference('user'),
            'secured_area');

        $this->assertInstanceOf(
            'Liip\FunctionalTestBundle\Tests\Test\WebTestCaseConfigTest',
            $loginAs
        );

        $this->client = static::makeClient();

        $path = '/admin';

        $crawler = $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'Logged in as foo bar.',
            $crawler->filter('p#user')->text()
        );

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );

        $this->assertSame(
            'Admin',
            $crawler->filter('h2')->text()
        );
    }

    /**
     * Log in as the user defined in the Data Fixtures.
     */
    public function testUserAuthenticationLoginAs()
    {
        $fixtures = $this->loadFixtures(array(
            'Liip\FunctionalTestBundle\DataFixtures\ORM\LoadUserData',
        ));

        $this->assertInstanceOf(
            'Doctrine\Common\DataFixtures\Executor\AbstractExecutor',
            $fixtures
        );

        $repository = $fixtures->getReferenceRepository();

        // One query to log in the first user
        $loginAs = $this->loginAs($repository->getReference('user'),
            'secured_area');

        $this->assertInstanceOf(
            'Liip\FunctionalTestBundle\Tests\Test\WebTestCaseConfigTest',
            $loginAs
        );

        $this->client = static::makeClient();

        // One query to load the second user
        $path = '/user/2';

        // There will be 2 queries, in the config the limit is 1,
        // an Exception will be thrown.
        $this->setExpectedException(
            'Liip\FunctionalTestBundle\Exception\AllowedQueriesExceededException'
        );

        $crawler = $this->client->request('GET', $path);
    }
}
