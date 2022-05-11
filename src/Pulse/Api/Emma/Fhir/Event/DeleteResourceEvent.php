<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Event;


class DeleteResourceEvent extends ModelEvent
{
    use EventDuration;

    protected $resourceId;

    protected $respondentId;

    public function __construct(\MUtil_Model_ModelAbstract $model, $resourceId)
    {
        parent::__construct($model);
        $this->resourceId = $resourceId;
    }

    public function getResourceId()
    {
        return $this->resourceId;
    }

    /**
     * @return mixed
     */
    public function getRespondentId()
    {
        return $this->respondentId;
    }

    /**
     * @param mixed $respondentId
     */
    public function setRespondentId($respondentId): void
    {
        $this->respondentId = $respondentId;
    }
}
