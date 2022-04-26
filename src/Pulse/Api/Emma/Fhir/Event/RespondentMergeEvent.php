<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Event;


use Symfony\Component\EventDispatcher\Event;

class RespondentMergeEvent extends Event
{
    protected $epd;

    protected $newPatientNr;

    protected $oldPatientNr;

    protected $organizationId;

    protected $ssn;

    protected $status;

    /**
     * @return mixed
     */
    public function getEpd()
    {
        return $this->epd;
    }

    /**
     * @return mixed
     */
    public function getNewPatientNr()
    {
        return $this->newPatientNr;
    }

    /**
     * @return mixed
     */
    public function getOldPatientNr()
    {
        return $this->oldPatientNr;
    }

    /**
     * @return mixed
     */
    public function getOrganizationId()
    {
        return $this->organizationId;
    }

    /**
     * @return mixed
     */
    public function getSsn()
    {
        return $this->ssn;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $epd
     */
    public function setEpd($epd): void
    {
        $this->epd = $epd;
    }

    /**
     * @param mixed $newPatientNr
     */
    public function setNewPatientNr($newPatientNr): void
    {
        $this->newPatientNr = $newPatientNr;
    }

    /**
     * @param mixed $oldPatientNr
     */
    public function setOldPatientNr($oldPatientNr): void
    {
        $this->oldPatientNr = $oldPatientNr;
    }

    /**
     * @param mixed $organizationId
     */
    public function setOrganizationId($organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    /**
     * @param mixed $ssn
     */
    public function setSsn($ssn): void
    {
        $this->ssn = $ssn;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

}
