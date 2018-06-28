<?php


namespace Prediction\Model;


use Gems\Rest\Exception\RestException;

class DataCollectionMissingDataException extends RestException
{
    public function __construct($errorMessage, $hint = null)
    {
        parent::construct($errorMessage, 101, 'missing_required_data', 409, $hint);
    }
}