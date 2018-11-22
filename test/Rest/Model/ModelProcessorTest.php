<?php


namespace GemsTest\Rest\Model;


use Gems\Rest\Model\ModelProcessor;
use PHPUnit\Framework\TestCase;
use Zalt\Loader\ProjectOverloader;

class ModelProcessorTest extends TestCase
{
    public function testGetValidator()
    {
        $processor = $this->getProcessor();
        $validator = new \Zend_Validate_NotEmpty();
        $expectedValidator = $processor->getValidator($validator);
        $this->assertInstanceOf(\Zend_Validate_Interface::class, $expectedValidator, 'Validator not instance of Zend Validator');
    }

    public function testGetNotExistingValidator()
    {
        $processor = $this->getProcessor();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Validator testValidatorThatDoesNotExist not found');
        $processor->getValidator('testValidatorThatDoesNotExist');
    }

    public function testGetNotValidValidator()
    {
        $processor = $this->getProcessor();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid validator provided to addValidator; must be string or Zend_Validate_Interface. Supplied array');
        $processor->getValidator(['an_array as validator should fail']);
    }

    protected function getProcessor()
    {
        $loader = $this->prophesize(ProjectOverloader::class);
        $model = $this->prophesize(\MUtil_Model_ModelAbstract::class);

        return new ModelProcessor($loader->reveal(), $model->reveal(), 0);
    }
}