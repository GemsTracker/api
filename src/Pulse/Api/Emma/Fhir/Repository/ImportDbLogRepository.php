<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Repository;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;

class ImportDbLogRepository
{
    /**
     * @var Adapter
     */
    protected $db;

    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    /**
     * Log import resource
     *
     * @param array $data import resource database info
     * @return int Effected rows
     */
    public function logImportResource(array $data)
    {
        $table = new TableGateway('gems__epd_import_resource', $this->db);
        return $table->insert($data);
    }
}
