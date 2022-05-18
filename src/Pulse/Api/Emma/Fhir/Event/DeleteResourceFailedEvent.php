<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Event;


class DeleteResourceFailedEvent extends ModelEvent
{

    protected $resourceId;
    /**
     * @var \Exception
     */
    protected $e;

    public function __construct(\MUtil_Model_ModelAbstract $model, \Exception $exception, $resourceId)
    {
        parent::__construct($model);
        $this->resourceId = $resourceId;
        $this->exception = $exception;
    }

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

    public function getResourceId()
    {
        return $this->resourceId;
    }
}
