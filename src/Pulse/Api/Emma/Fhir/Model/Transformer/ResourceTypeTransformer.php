<?php

namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\IncorrectDataException;
use Gems\Rest\Exception\MissingDataException;

class ResourceTypeTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $resourceType;

    public function __construct($resourceType)
    {
        $this->resourceType = $resourceType;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (!isset($row['resourceType'])) {
            throw new MissingDataException('resourceType is missing');
        }
        if ($row['resourceType'] !== $this->resourceType) {
            if (strtolower($row['resourceType']) !== strtolower($this->resourceType)) {
                throw new IncorrectDataException(sprintf('expected %s as resourceType', $this->resourceType));
            }
            $row['resourceType'] = $this->resourceType;
        }

        return $row;
    }
}
