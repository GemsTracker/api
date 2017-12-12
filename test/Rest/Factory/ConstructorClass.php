<?php


namespace GemsTest\Rest\Factory;


class ConstructorClass
{
    public $empty;

    public function __construct(EmptyClass $empty)
    {
        $this->emptyClass = $empty;
    }
}