<?php

namespace Gems\Rest\Exception;

class MissingDataException extends \Exception
{
    protected $missingData;

    public function __construct($message = "", $missingData = null, $code = 0, \Throwable $previous = null)
    {
        if (isset($missingData)) {
            if (!is_array($missingData)) {
                $missingData = [$missingData];
            }
            $this->missingData = $missingData;
        }
        parent::__construct($message, $code, $previous);
    }

    public function getMissingData()
    {
        return $this->missingData;
    }
}
