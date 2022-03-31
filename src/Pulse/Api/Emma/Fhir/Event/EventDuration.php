<?php

namespace Pulse\Api\Emma\Fhir\Event;

trait EventDuration
{
    protected $start;

    public function getDurationInSeconds()
    {
        if ($this->start instanceof \DateTimeInterface) {
            $now = new \DateTimeImmutable();
            return $now->getTimestamp() - $this->start->getTimestamp();
        }
        if (is_numeric($this->start)) {
            $now = microtime(true);
            return $now - $this->start;
        }
        return null;
    }

    public function setStart($start)
    {
        $this->start = $start;
    }
}
