<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\IncorrectDataException;
use Gems\Rest\Exception\MissingDataException;

class SourceIdTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $sourceIdField;

    public function __construct($sourceIdField)
    {
        $this->sourceIdField = $sourceIdField;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (!isset($row['id'])) {
            throw new MissingDataException('id is missing');
        }
        $row[$this->sourceIdField] = $row['id'];


        return $row;
    }
}
