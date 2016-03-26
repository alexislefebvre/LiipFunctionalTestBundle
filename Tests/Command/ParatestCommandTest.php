<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Tests\Command;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class ParatestCommandTest extends WebTestCase
{
    /**
     * Test paratestCommand.
     */
    public function testParatest()
    {
        $kernel = $this->getContainer()->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $content = $this->runCommand('test:run');

        $this->assertContains('Running phpunit in 5 processes', $content);
        $this->assertContains('Initial schema created', $content);
        $this->assertNotContains('Error : Install paratest first', $content);
        $this->assertContains('Done...Running test.', $content);
    }
}
