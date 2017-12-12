<?php


namespace GemsTest\Rest\Factory;


class DefaultValueClass
{
    public $defaultVariable;

    public function __construct($default = 'This should be the default value')
    {
        $this->defaultVariable = $default;
    }
}