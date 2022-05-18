<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Repository;


use GemsTest\Rest\Test\DbTestCase;
use Pulse\Api\Emma\Fhir\Repository\ImportDbLogRepository;

class ImportDbLogRepositoryTest extends DbTestCase
{
    public function testLogImportResource()
    {
        $repository = $this->getRepository();

        $data = [
            'geir_source' => 'testSource',
            'geir_type' => 'testType',
            'geir_status' => 'testing',
        ];

        $repository->logImportResource($data);

        $this->assertTableRowCount(1, 'gems__epd_import_resource');
    }

    public function testLogImportResourceFail()
    {
        $repository = $this->getRepository();

        $data = [];

        $this->expectException(\InvalidArgumentException::class);
        $repository->logImportResource($data);

        $this->assertTableRowCount(0, 'gems__epd_import_resource');
    }

    protected function getRepository()
    {
        return new ImportDbLogRepository($this->db);
    }
}
