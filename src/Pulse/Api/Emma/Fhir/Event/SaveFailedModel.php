<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Event;


class SaveFailedModel extends ModelEvent
{
    /**
     * @var \Exception
     */
    protected $exception;

    /**
     * @var array
     */
    protected $saveData;

    public function getException()
    {
        return $this->exception;
    }

    public function getSaveData()
    {
        return $this->saveData;
    }

    public function setException(\Exception $exception)
    {
        $this->exception = $exception;
    }

    public function setSaveData(array $data)
    {
        $this->saveData = $data;
    }
}
