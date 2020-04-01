<?php
declare(strict_types=1);

namespace Test\Acceptance;

use Behat\Behat\Context\Context;
use LeanpubBookClub\Application\ApplicationInterface;

abstract class FeatureContext implements Context
{
    /**
     * @var ServiceContainerForAcceptanceTesting
     */
    private ServiceContainerForAcceptanceTesting $serviceContainer;

    public function __construct()
    {
        $this->serviceContainer = new ServiceContainerForAcceptanceTesting();
    }

    /**
     * @return array<object>
     */
    protected function dispatchedEvents(): array
    {
        return $this->serviceContainer->eventDispatcherSpy()->dispatchedEvents();
    }

    protected function clearEvents(): void
    {
        $this->serviceContainer->eventDispatcherSpy()->clearEvents();
    }

    protected function application(): ApplicationInterface
    {
        return $this->serviceContainer->application();
    }

    protected function serviceContainer(): ServiceContainerForAcceptanceTesting
    {
        return $this->serviceContainer;
    }

    protected function authorTimeZone(): string
    {
        return $this->serviceContainer()->authorTimeZone()->asString();
    }
}
