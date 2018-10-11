<?php


namespace Gems\Rest\Model;


class ModelProcessor
{
    /**
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    public function __construct(\MUtil_Model_ModelAbstract $model)
    {
        $this->model = $model;
    }

    public function save($row)
    {
        //$row = $this->validateRow($row);

        return $this->model->save($row);
    }
}