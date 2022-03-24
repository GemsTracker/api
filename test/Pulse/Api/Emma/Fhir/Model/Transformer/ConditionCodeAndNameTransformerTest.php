<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use PHPUnit\Framework\TestCase;
use Pulse\Api\Emma\Fhir\Model\Transformer\ConditionCodeAndNameTransformer;
use PulseTest\Rest\Api\Emma\Fhir\Model\MockConditionModel;

class ConditionCodeAndNameTransformerTest extends TestCase
{
    use MockConditionModel;

    public function testNoCode()
    {
        $model = $this->getConditionModel();

        $transformer = new ConditionCodeAndNameTransformer();
        $data = [];

        $result = $transformer->transformRowBeforeSave($model, $data);

        $this->assertEquals($data, $result);
    }

    public function testNullCode()
    {
        $model = $this->getConditionModel();

        $transformer = new ConditionCodeAndNameTransformer();
        $data = [
            'code' => null
        ];

        $result = $transformer->transformRowBeforeSave($model, $data);

        $this->assertEquals($data, $result);
    }

    public function testValidCode()
    {
        $model = $this->getConditionModel();

        $transformer = new ConditionCodeAndNameTransformer();
        $data = [
            'code' => [
                 'coding' => [
                     [
                         'code' => 'TEST01',
                     ]
                 ],
            ],
        ];

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gmco_code'] = 'TEST01';

        $this->assertEquals($expected, $result);
    }

    public function testDisplayWithoutCode()
    {
        $model = $this->getConditionModel();

        $transformer = new ConditionCodeAndNameTransformer();
        $data = [
            'code' => [
                 'coding' => [
                     [
                         'display' => 'a test condition',
                     ]
                 ],
            ],
        ];

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;

        $this->assertEquals($expected, $result);
    }

    public function testDisplayWithCode()
    {
        $model = $this->getConditionModel();

        $transformer = new ConditionCodeAndNameTransformer();
        $data = [
            'code' => [
                 'coding' => [
                     [
                         'code' => 'TEST01',
                         'display' => 'a test condition',
                     ]
                 ],
            ],
        ];

        $result = $transformer->transformRowBeforeSave($model, $data);

        $expected = $data;
        $expected['gmco_code'] = 'TEST01';
        $expected['gmco_name'] = 'a test condition';

        $this->assertEquals($expected, $result);
    }
}
