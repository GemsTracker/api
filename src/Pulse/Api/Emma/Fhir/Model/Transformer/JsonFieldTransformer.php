<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


class JsonFieldTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var array
     */
    protected $jsonFields;

    public function __construct(array $jsonFields)
    {
        $this->jsonFields = $jsonFields;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        foreach($this->jsonFields as $fieldName) {
            if (array_key_exists($fieldName, $row) && is_array($row[$fieldName])) {
                $row[$fieldName] = json_encode($row[$fieldName]);
            }
        }
        return $row;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key => $row) {
            foreach($this->jsonFields as $fieldName) {
                if (array_key_exists($fieldName, $row) && is_string($row[$fieldName])) {
                    $data[$key][$fieldName] = json_decode($row[$fieldName], true);
                }
            }
        }

        return $data;
    }
}
