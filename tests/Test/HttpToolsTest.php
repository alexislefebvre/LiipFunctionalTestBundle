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

namespace Liip\FunctionalTestBundle\Tests\Test;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use PHPUnit\Framework\AssertionFailedError;

class HttpToolsTest extends WebTestCase
{
    /** @var \Symfony\Bundle\FrameworkBundle\Client client */
    private $client = null;

    public function setUp(): void
    {
        $this->client = static::makeClient();
    }

    public function testGetUrl(): void
    {
        $path = $this->getUrl(
            'liipfunctionaltestbundle_user',
            [
                'userId' => 1,
                'get_parameter' => 'abc',
            ]
        );

        $this->assertInternalType('string', $path);

        $this->assertSame($path, '/user/1?get_parameter=abc');
    }

    /**
     * Call methods from Symfony to ensure the Controller works.
     */
    public function testIndex(): void
    {
        $path = '/';

        /** @var \Symfony\Component\DomCrawler\Crawler $crawler */
        $crawler = $this->client->request('GET', $path);

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'Not logged in.',
            $crawler->filter('p#user')->text()
        );

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );
    }

    /**
     * Call methods from the parent class.
     */

    /**
     * @depends testIndex
     */
    public function testIndexAssertStatusCode(): void
    {
        $this->loadFixtures([]);

        $path = '/';

        $this->client->request('GET', $path);

        $this->assertStatusCode(200, $this->client);
    }

    /**
     * Check the failure message returned by assertStatusCode().
     */
    public function testAssertStatusCodeFail(): void
    {
        $this->loadFixtures([]);

        $path = '/';

        $this->client->request('GET', $path);

        try {
            $this->assertStatusCode(-1, $this->client);
        } catch (AssertionFailedError $e) {
            $this->assertStringStartsWith(
                'HTTP/1.1 200 OK',
                $e->getMessage()
            );

            $this->assertStringEndsWith(
                'Failed asserting that 200 matches expected -1.',
                $e->getMessage()
            );

            return;
        }

        $this->fail('Test failed.');
    }

    /**
     * Check the failure message returned by assertStatusCode().
     */
    public function testAssertStatusCodeException(): void
    {
        $this->loadFixtures([]);

        $path = '/user/2';

        $this->client->request('GET', $path);

        try {
            $this->assertStatusCode(-1, $this->client);
        } catch (AssertionFailedError $e) {
            $string = <<<'EOF'
No user found
Failed asserting that 404 matches expected -1.
EOF;
            $this->assertSame($string, $e->getMessage());

            return;
        }

        $this->fail('Test failed.');
    }

    /**
     * @depends testIndex
     */
    public function testIndexIsSuccesful(): void
    {
        $this->loadFixtures([]);

        $path = '/';

        $this->client->request('GET', $path);

        $this->isSuccessful($this->client->getResponse());
    }

    /**
     * @depends testIndex
     */
    public function testIndexFetchCrawler(): void
    {
        $this->loadFixtures([]);

        $path = '/';

        $crawler = $this->fetchCrawler($path);

        $this->assertInstanceOf(
            'Symfony\Component\DomCrawler\Crawler',
            $crawler
        );

        $this->assertSame(1,
            $crawler->filter('html > body')->count());

        $this->assertSame(
            'Not logged in.',
            $crawler->filter('p#user')->text()
        );

        $this->assertSame(
            'LiipFunctionalTestBundle',
            $crawler->filter('h1')->text()
        );
    }

    /**
     * @depends testIndex
     */
    public function testIndexFetchContent(): void
    {
        $this->loadFixtures([]);

        $path = '/';

        $content = $this->fetchContent($path);

        $this->assertInternalType('string', $content);

        $this->assertContains(
            '<h1>LiipFunctionalTestBundle</h1>',
            $content
        );
    }

    public function test404Error(): void
    {
        $this->loadFixtures([]);

        $path = '/missing_page';

        $this->client->request('GET', $path);

        $this->assertStatusCode(404, $this->client);

        $this->isSuccessful($this->client->getResponse(), false);
    }

    /**
     * Throw an Exception in the try/catch block and check the failure message
     * returned by assertStatusCode().
     */
    public function testIsSuccessfulException(): void
    {
        $this->loadFixtures([]);

        $response = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
            ->disableOriginalConstructor()
            ->setMethods(['getContent'])
            ->getMock();

        $response->expects($this->any())
            ->method('getContent')
            ->will($this->throwException(new \Exception('foo')));

        try {
            $this->isSuccessful($response);
        } catch (AssertionFailedError $e) {
            $string = <<<'EOF'
The Response was not successful: foo
Failed asserting that false is true.
EOF;
            $this->assertSame($string, $e->getMessage());

            return;
        }

        $this->fail('Test failed.');
    }

    public function testMakeClient(): void
    {
        $this->assertInstanceOf(
            'Symfony\Bundle\FrameworkBundle\Client',
            static::makeClient()
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->client = null;
    }
}
