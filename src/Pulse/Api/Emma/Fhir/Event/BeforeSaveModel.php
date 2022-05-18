<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Event;


class BeforeSaveModel extends ModelEvent
{
    /**
     * @var array data before save
     */
    protected $beforeData;

    public function setBeforeData(array $data)
    {
        $this->beforeData = $data;
    }

    public function getBeforeData()
    {
        return $this->beforeData;
    }
}
