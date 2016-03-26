<?php

namespace Liip\FunctionalTestBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Command used to update the project.
 */
class RunParatestCommand extends ContainerAwareCommand
{
    private $container;
    private $configuration;
    private $output;
    private $process = 5;
    private $testDbPath;
    // Relative path to phpunit.
    private $phpunit = '../vendor/bin/phpunit';

    /**
     * Configuration of the command.
     */
    protected function configure()
    {
        $this
            ->setName('test:run')
            ->setDescription('Run phpunit tests with multiple process')
        ;
    }

    protected function prepare()
    {
        $this->configuration = $this->getContainer()->hasParameter('liip_functional_test');
        $paratestCfg = (!isset($this->configuration['paratest'])) ? array('process' => $this->process, 'phpunit' => $this->phpunit) : $this->configuration['paratest'];

        $this->process = (!empty($this->configuration['process'])) ? $paratestCfg['process'] : $this->process;
        $this->phpunit = (!empty($this->configuration['phpunit'])) ? $paratestCfg['phpunit'] : $this->phpunit;
        $this->testDbPath = $this->getContainer()->getParameter('kernel.cache_dir').'/';

        $this->output->writeln("Cleaning old dbs in $this->testDbPath ...");
        if (!is_dir($this->testDbPath)) {
            mkdir($this->testDbPath, 0755, true);
        }

        $cleanProcess = new Process("rm -fr $this->testDbPath/dbTest.db $this->testDbPath/dbTest*.db*");
        $cleanProcess->run();
        $this->output->writeln("Creating Schema in $this->testDbPath ...");
        $createProcess = new Process('php app/console doctrine:schema:create --env=test');
        $createProcess->run();

        $this->output->writeln('Initial schema created');
        $populateProcess = new Process("php app/console doctrine:fixtures:load -n --fixtures $this->testDbPath/../src/overlord/AppBundle/Tests/DataFixtures/ORM/ --env=test");
        $populateProcess->run();

        $this->output->writeln('Initial schema populated, duplicating....');
        for ($a = 0; $a < $this->process; ++$a) {
            $test = new Process("cp $this->testDbPath/dbTest.db ".$this->testDbPath."test/dbTest$a.db");
            $test->run();
//            copy($this->testDbPath.'dbTest.db',
//                $this->testDbPath.'dbTest'.$a.'.db');
        }
    }

    /**
     * Content of the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->prepare();

        $paratestPath = __DIR__.'/../vendor/bin/paratest';

        if (is_file($paratestPath) !== true) {
            $this->output->writeln('Error : Install paratest first');
        } else {
            $this->output->writeln('Done...Running test.');
            $runProcess = new Process($paratestPath.' '.
                '-c '.__DIR__.'/../phpunit.xml.dist '.
                '--phpunit '.__DIR__.'/'.$this->phpunit.' '.
                '--runner WrapRunner  -p '.$this->process.' '.
                // Don't launch all the tests, that may create an infinite
                // loop if this current file is tested.
                __DIR__.'/../Tests/Test/');
            $runProcess->run(function ($type, $buffer) use ($output) {
                $output->write($buffer);
            });
        }
    }
}
