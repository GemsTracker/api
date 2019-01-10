<?php


namespace PulseTest\Rest\Api\Model;


use PHPUnit\Framework\TestCase;
use Pulse\Api\Model\ApiModelTranslator;

class ApiModelTranslatorTest extends TestCase
{
    public function testTranslateRowEmptyTranslations()
    {
        $translations = [];
        $translator = $this->getTranslator($translations);
        $row = [];
        $result = $translator->translateRow($row);
        $this->assertEmpty($result);
    }

    public function testTranslateRowTranslations()
    {
        $translations = [
            'original' => 'translation',
        ];
        $translator = $this->getTranslator($translations);
        $row = ['original' => 'some value'];
        $result = $translator->translateRow($row);
        $expectedResult = [
            'translation' => 'some value',
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testTranslateRowTranslationsReverse()
    {
        $translations = [
            'original' => 'translation',
        ];
        $translator = $this->getTranslator($translations);
        $row = ['translation' => 'some value'];
        $result = $translator->translateRow($row, true);
        $expectedResult = [
            'original' => 'some value',
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testTranslateRowTranslationsMultiDimensional()
    {
        $translations = [
            'original' => [
                'original_sub1' => 'translated_sub1',
            ],
        ];
        $translator = $this->getTranslator($translations);
        $row = [
            'original' => [
                'original_sub1' => 'some sub value',
            ]
        ];
        $result = $translator->translateRow($row);
        $expectedResult = [
            'original' => [
                'translated_sub1' => 'some sub value'
            ],
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testTranslateRowTranslationsMultiDimensionalReversed()
    {
        $translations = [
            'original' => [
                'original_sub1' => 'translated_sub1',
            ],
        ];
        $translator = $this->getTranslator($translations);
        $row = [
            'original' => [
                'translated_sub1' => 'some sub value',
            ]
        ];
        $result = $translator->translateRow($row, true);
        $expectedResult = [
            'original' => [
                'original_sub1' => 'some sub value'
            ],
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testSetTranslationsManually()
    {
        $translations = [
            'original' => 'translation',
        ];
        $translator = $this->getTranslator();
        $translator->setTranslations($translations);
        $row = ['original' => 'some value'];
        $result = $translator->translateRow($row);
        $expectedResult = [
            'translation' => 'some value',
        ];

        $this->assertEquals($expectedResult, $result);
    }



    protected function getTranslator($translations = null)
    {
        return new ApiModelTranslator($translations);
    }
}