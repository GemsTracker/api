<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Event;


use Symfony\Component\EventDispatcher\Event;

class ModelEvent extends Event
{
    /**
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    public function __construct(\MUtil_Model_ModelAbstract $model)
    {
        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model;
    }
}
