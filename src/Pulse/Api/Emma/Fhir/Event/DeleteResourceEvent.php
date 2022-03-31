<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Event;


class DeleteResourceEvent extends ModelEvent
{
    use EventDuration;

    protected $resourceId;

    public function __construct(\MUtil_Model_ModelAbstract $model, $resourceId)
    {
        parent::__construct($model);
        $this->resourceId = $resourceId;
    }

    public function getResourceId()
    {
        return $this->resourceId;
    }
}
