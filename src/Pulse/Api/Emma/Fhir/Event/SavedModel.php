<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Event;

class SavedModel extends ModelEvent
{
    /**
     * @var array New data after save
     */
    protected $newData;

    protected $oldData;

    protected $start;

    public function getNewData()
    {
        return $this->newData;
    }

    public function getOldData()
    {
        return $this->oldData;
    }

    public function getDurationInSeconds()
    {
        if ($this->start instanceof \DateTimeInterface) {
            $now = new \DateTimeImmutable();
            return $now->getTimestamp() - $this->start->getTimestamp();
        }
        return null;
    }

    public function setNewData(array $newData)
    {
        $this->newData = $newData;
    }

    public function setOldData(array $oldData)
    {
        $this->oldData = $oldData;
    }

    public function setStart(\DateTimeInterface $start)
    {
        $this->start = $start;
    }
}
