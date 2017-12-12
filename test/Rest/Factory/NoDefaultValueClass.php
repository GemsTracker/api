<?php


namespace GemsTest\Rest\Factory;


class NoDefaultValueClass
{
    public $defaultVariable;

    public function __construct($default)
    {
        $this->defaultVariable = $default;
    }
}