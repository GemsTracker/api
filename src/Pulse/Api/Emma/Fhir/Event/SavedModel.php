<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Event;

class SavedModel extends ModelEvent
{
    use EventDuration;

    /**
     * @var array New data after save
     */
    protected $newData;

    protected $oldData;

    public function getNewData()
    {
        return $this->newData;
    }

    public function getOldData()
    {
        return $this->oldData;
    }

    public function setNewData(array $newData)
    {
        $this->newData = $newData;
    }

    public function setOldData(array $oldData)
    {
        $this->oldData = $oldData;
    }
}
