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

/* Used by annotations */
use Liip\FunctionalTestBundle\Test\Html5WebTestCase;

/*
 * Use mocked methods instead of inheriting from the class.
 */
class Html5WebTestCaseMockTest extends \PHPUnit_Framework_TestCase
{
    private function getMockedClass()
    {
        return $this->getMockBuilder('Liip\FunctionalTestBundle\Test\Html5WebTestCase')
            ->disableOriginalConstructor();
    }

    private function addMethod($mock, $function, $return)
    {
        $mock->expects($this->once())
            ->method($function)
            ->willReturn($return);
    }

    private function addMethodGetHtml5ValidatorServiceUrl($mock, $return)
    {
        $this->addMethod($mock, 'getHtml5ValidatorServiceUrl', $return);
    }

    private function addMethodGetValidationServiceAvailable($mock, $return)
    {
        $this->addMethod($mock, 'getValidationServiceAvailable', $return);
    }

    private function addMethodValidateHtml5($mock, $return)
    {
        $this->addMethod($mock, 'validateHtml5', $return);
    }

    public function testIsValidationServiceAvailable()
    {
        /** @var Html5WebTestCase $mock */
        $mock = $this->getMockedClass()
            ->setMethods(array('getHtml5ValidatorServiceUrl'))
            ->getMock();

        $this->addMethodGetHtml5ValidatorServiceUrl($mock, null);

        // The "/" URL is unreachable.
        $this->assertFalse(
            $mock->isValidationServiceAvailable()
        );
    }

    public function testValidateHtml5()
    {
        /** @var Html5WebTestCase $mock */
        $mock = $this->getMockedClass()
            ->setMethods(array('getHtml5ValidatorServiceUrl'))
            ->getMock();

        $this->addMethodGetHtml5ValidatorServiceUrl($mock, null);

        $this->assertFalse(
            $mock->validateHtml5('')
        );
    }

    public function testAssertIsValidHtml5()
    {
        /** @var Html5WebTestCase $mock */
        $mock = $this->getMockedClass()
            ->setMethods(array('getValidationServiceAvailable', 'validateHtml5'))
            ->getMock();

        $this->addMethodGetValidationServiceAvailable($mock, true);

        // Return successful result from validator.
        $res = new \ArrayObject();
        $res->messages = array();

        $this->addMethodValidateHtml5($mock, $res);

        $mock->assertIsValidHtml5('');
    }

    /**
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testAssertIsValidHtml5Fail()
    {
        /** @var Html5WebTestCase $mock */
        $mock = $this->getMockedClass()
            ->setMethods(array('getValidationServiceAvailable', 'validateHtml5'))
            ->getMock();

        $this->addMethodGetValidationServiceAvailable($mock, true);

        // Return error messages from validator.
        $res = new \ArrayObject();
        $res->messages = array(
            (object) array('type' => 'error', 'message' => 'foo', 'lastLine' => 1),
        );

        $this->addMethodValidateHtml5($mock, $res);

        $mock->assertIsValidHtml5('');
    }

    public function testAssertIsValidHtml5FailWithIgnores()
    {
        /* @see http://gianarb.it/blog/symfony-unit-test-controller-with-phpunit#expectations */
        /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->any())
            ->method('getParameter')
            ->will($this->onConsecutiveCalls(array('#foo#'), array('#bar#')));

        /** @var Html5WebTestCase $mock */
        $mock = $this->getMockedClass()
            ->setMethods(array('getValidationServiceAvailable', 'validateHtml5', 'getContainer'))
            ->getMock();

        $this->addMethodGetValidationServiceAvailable($mock, true);

        // Return error messages from validator.
        $res = new \ArrayObject();
        $res->messages = array(
            (object) array('type' => 'error', 'message' => 'no', 'lastLine' => 1, 'extract' => 'no'),
            (object) array('type' => 'error', 'message' => '', 'lastLine' => 2, 'extract' => 'no'),
            (object) array('type' => 'error', 'message' => 'foo', 'lastLine' => 3),
            (object) array('type' => 'error', 'message' => 'bar', 'lastLine' => 4, 'extract' => 'bar'),
        );

        $this->addMethodValidateHtml5($mock, $res);

        $mock->expects($this->any())
            ->method('getContainer')
            ->willReturn($container);

        $string = <<<'EOF'
HTML5 validation failed [baz]:
  Line 1: no
  Line 2: Empty error message about no

Failed asserting that false is true.
EOF;

        /* @see https://github.com/sebastianbergmann/phpunit/blob/b12b9c37e382c096b93c3f26e7395775f59a5eea/tests/Framework/AssertTest.php#L3560-L3574 */
        try {
            $mock->assertIsValidHtml5('', 'baz');
        } catch (\PHPUnit_Framework_AssertionFailedError $e) {
            $this->assertSame($string, $e->getMessage());

            return;
        }

        $this->fail('Test failed.');
    }

    public function testAssertIsValidHtml5SkipTestServiceNotAvailable()
    {
        /** @var Html5WebTestCase $mock */
        $mock = $this->getMockedClass()
            ->setMethods(
                array(
                    'getValidationServiceAvailable',
                    'getHtml5ValidatorServiceUrl',
                )
            )
            ->getMock();

        $this->addMethodGetValidationServiceAvailable($mock, false);

        $this->addMethodGetHtml5ValidatorServiceUrl($mock, 'http://localhost/');

        try {
            $mock->assertIsValidHtml5('');
        } catch (\PHPUnit_Framework_SkippedTestError $e) {
            $this->assertSame(
                'HTML5 Validator service not found at \'http://localhost/\' !',
                $e->getMessage()
            );

            return;
        }

        $this->fail('Test failed.');
    }

    public function testAssertIsValidHtml5SkipTestServiceReturnFalse()
    {
        /** @var Html5WebTestCase $mock */
        $mock = $this->getMockedClass()
            ->setMethods(
                array(
                    'getValidationServiceAvailable',
                    'getHtml5ValidatorServiceUrl',
                    'validateHtml5',
                )
            )
            ->getMock();

        $this->addMethodGetValidationServiceAvailable($mock, true);

        $this->addMethodGetHtml5ValidatorServiceUrl($mock, 'http://localhost/');

        // This will force the test to skip.
        $this->addMethodValidateHtml5($mock, false);

        try {
            $mock->assertIsValidHtml5('');
        } catch (\PHPUnit_Framework_SkippedTestError $e) {
            $this->assertSame(
                'HTML5 Validator service not found at \'http://localhost/\' !',
                $e->getMessage()
            );

            return;
        }

        $this->fail('Test failed.');
    }

    public function testAssertIsValidHtml5Snippet()
    {
        /** @var Html5WebTestCase $mock */
        $mock = $this->getMockedClass()
            ->setMethods(array('getValidationServiceAvailable', 'validateHtml5'))
            ->getMock();

        $this->addMethodGetValidationServiceAvailable($mock, true);

        // Return successful result from validator.
        $res = new \ArrayObject();
        $res->messages = array();

        $this->addMethodValidateHtml5($mock, $res);

        $mock->assertIsValidHtml5Snippet('');
    }

    /**
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testAssertIsValidHtml5SnippetFail()
    {
        /** @var Html5WebTestCase $mock */
        $mock = $this->getMockedClass()
            ->setMethods(array('getValidationServiceAvailable', 'validateHtml5'))
            ->getMock();

        $this->addMethodGetValidationServiceAvailable($mock, true);

        // Return error messages from validator.
        $res = new \ArrayObject();
        $res->messages = array(
            (object) array('type' => 'error', 'message' => 'foo', 'lastLine' => 1),
        );

        $this->addMethodValidateHtml5($mock, $res);

        $mock->assertIsValidHtml5Snippet('<p>Hello World!</p>');
    }
}
