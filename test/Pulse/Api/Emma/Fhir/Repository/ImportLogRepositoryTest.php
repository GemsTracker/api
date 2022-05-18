<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Repository;


use Laminas\Log\PsrLoggerAdapter;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportLogRepository;

class ImportLogRepositoryTest extends TestCase
{
    protected $testDirectory;

    protected function setUp()
    {
        parent::setUp();
        $this->testDirectory = vfsStream::setup('testDirectory');
    }

    public function testGetImportLogger()
    {
        $this->assertFalse($this->testDirectory->hasChild('testEpdName-import.log'));
        $repository = $this->getRepository();
        $logger = $repository->getImportLogger();

        $this->assertInstanceOf(PsrLoggerAdapter::class, $logger);
        $this->assertTrue($this->testDirectory->hasChild('testEpdName-import.log'));
    }

    protected function getRepository()
    {
        $epdRepositoryProphecy = $this->prophesize(EpdRepository::class);
        $epdRepositoryProphecy->getEpdName()->willReturn('testEpdName');

        $config = [
            'log' => [
                'logDir' => vfsStream::url('testDirectory'),
            ],
        ];

        return new ImportLogRepository($epdRepositoryProphecy->reveal(), $config);
    }
}
