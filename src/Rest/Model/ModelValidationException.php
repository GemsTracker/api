<?php


namespace Gems\Rest\Model;


use Throwable;

class ModelValidationException extends ModelException
{
    /**
     * @var array List of errors
     */
    protected $errors;

    public function __construct($message = "", $errors, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Get the errors supplied in the Exception
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}