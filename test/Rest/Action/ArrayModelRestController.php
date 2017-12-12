<?php


namespace GemsTest\Rest\Action;


use Gems\Rest\Action\ModelRestControllerAbstract;

class ArrayModelRestController extends ModelRestControllerAbstract
{
    protected $data = [];

    protected $headers = [];

    public function createModel()
    {
        if ($this->model instanceof \MUtil_Model_ModelAbstract) {
            return $this->model;
        }

        return new \Gems_Model_PlaceholderModel('emptyModel', $this->headers, $this->data);
    }

    public function setData($data)
    {
        if (!$this->headers) {
            $firstRow = reset($data);
            $this->setHeaders(array_keys($firstRow));
        }

        $this->data = $data;
    }

    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    public function setModel(\MUtil_Model_ModelAbstract $model)
    {
        $this->model = $model;
    }
}