<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Event;


class ModelImport extends ModelEvent
{
    /**
     * @var array
     */
    protected $importData = [];

    public function setImportData(array $importData)
    {
        $this->importData = $importData;
    }

    public function getImportData()
    {
        return $this->importData;
    }
}
