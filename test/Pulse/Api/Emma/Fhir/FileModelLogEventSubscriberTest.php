<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir;


use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\ModelLogEventSubscriber;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportDbLogRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportLogRepository;

class FileModelLogEventSubscriberTest extends TestCase
{
    public function logFileImportStart()
    {
        $eventSubscriber = $this->getEventSubscriber();
    }

    protected function getEventSubscriber()
    {
        $epdRepositoryProphecy = $this->prophesize(EpdRepository::class);

        $importLogRepositoryProphecy = $this->prophesize(ImportLogRepository::class);
        $importDbLogRepositoryProphecy = $this->prophesize(ImportDbLogRepository::class);

        $currentUserRepositoryProphecy = $this->prophesize(CurrentUserRepository::class);

        return new ModelLogEventSubscriber(
            $epdRepositoryProphecy->reveal(),
            $importLogRepositoryProphecy->reveal(),
            $importDbLogRepositoryProphecy->reveal(),
            $currentUserRepositoryProphecy->reveal()
        );
    }

}
