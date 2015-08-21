<?php

namespace Nelmio\ApiDocBundle\Tests\Command;

use Nelmio\ApiDocBundle\Tests\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

class CommandTestCase extends WebTestCase
{
    /**
     * @var ApplicationTester
     */
    protected $tester;

    protected function setUp()
    {
        parent::setUp();

        $this->getContainer();

        $this->tester = new ApplicationTester(
            $this->createApplication(static::$kernel)
        );
    }

    /**
     * @param KernelInterface $kernel
     * @param bool|false $catchException
     * @param bool|false $autoExit
     * @return Application
     */
    protected function createApplication(KernelInterface $kernel, $catchException = false, $autoExit = false)
    {
        $application = new Application($kernel);
        $application->setCatchExceptions($catchException);
        $application->setAutoExit($autoExit);

        return $application;
    }

}
