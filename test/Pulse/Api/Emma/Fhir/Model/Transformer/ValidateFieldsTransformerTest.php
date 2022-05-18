<?php

namespace PulseTest\Rest\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Model\ModelValidationException;
use Laminas\Validator\NotEmpty;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Pulse\Api\Emma\Fhir\Model\RespondentModel;
use Pulse\Api\Emma\Fhir\Model\Transformer\ValidateFieldsTransformer;
use Zalt\Loader\ProjectOverloader;

class ValidateFieldsTransformerTest extends TestCase
{
    public function testNoValidations()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn([]);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn([]);
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn([]);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(null);

        $transformer = $this->getTransformer();

        $data = [];

        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);

        $this->assertEquals($result, $data);
    }

    public function testSingleLaminasValidations()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn([]);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn(NotEmpty::class);
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn([]);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(null);

        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => 1];

        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);

        $this->assertEquals($result, $data);
    }

    public function testSingleZend1Validations()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn([]);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn('NotEmpty');
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn([]);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(null);

        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => 1];

        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);

        $this->assertEquals($result, $data);
    }

    public function testMultiValidations()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn([
            NotEmpty::class,
        ]);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn(NotEmpty::class);
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn([]);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(null);

        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => 1];

        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);

        $this->assertEquals($result, $data);
    }

    public function testSingleMultiValidations()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn(NotEmpty::class);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn(null);
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn([]);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(null);

        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => 1];

        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);

        $this->assertEquals($result, $data);
    }

    public function testMultiSingleValidations()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn(null);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn([NotEmpty::class]);
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn([]);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(null);

        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => 1];

        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);

        $this->assertEquals($result, $data);
    }

    public function testRequiredFields()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn(null);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn();
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn(true);
        $modelProphecy->get('gr2o_patient_nr', 'key')->willReturn(false);
        $modelProphecy->get('gr2o_patient_nr', 'autoInsertNotEmptyValidator')->willReturn(null);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(null);


        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => 1];

        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);

        $this->assertEquals($result, $data);
    }

    public function testTypedFieldsFloat()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn(null);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn(null);
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn(true);
        $modelProphecy->get('gr2o_patient_nr', 'key')->willReturn(false);
        $modelProphecy->get('gr2o_patient_nr', 'autoInsertNotEmptyValidator')->willReturn(null);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(true);
        $modelProphecy->get('gr2o_patient_nr', 'type')->willReturn(\MUtil_Model::TYPE_NUMERIC);


        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => 1];

        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);

        $this->assertEquals($result, $data);
    }

    public function testTypedFieldsDate()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn(null);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn(null);
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn(true);
        $modelProphecy->get('gr2o_patient_nr', 'key')->willReturn(false);
        $modelProphecy->get('gr2o_patient_nr', 'autoInsertNotEmptyValidator')->willReturn(null);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(true);
        $modelProphecy->get('gr2o_patient_nr', 'type')->willReturn(\MUtil_Model::TYPE_DATE);


        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => '2022-02-11'];

        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);

        $this->assertEquals($result, $data);
    }

    public function testTypedFieldsDateTime()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn(null);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn(null);
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn(true);
        $modelProphecy->get('gr2o_patient_nr', 'key')->willReturn(false);
        $modelProphecy->get('gr2o_patient_nr', 'autoInsertNotEmptyValidator')->willReturn(null);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(true);
        $modelProphecy->get('gr2o_patient_nr', 'type')->willReturn(\MUtil_Model::TYPE_DATETIME);


        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => new \MUtil_Date('2022-02-11 01:23:45')];

        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);

        $this->assertEquals($result, $data);
    }

    public function testTypedFieldsString()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn(null);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn(null);
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn(true);
        $modelProphecy->get('gr2o_patient_nr', 'key')->willReturn(false);
        $modelProphecy->get('gr2o_patient_nr', 'autoInsertNotEmptyValidator')->willReturn(null);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(true);
        $modelProphecy->get('gr2o_patient_nr', 'type')->willReturn(\MUtil_Model::TYPE_STRING);


        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => 'lala'];

        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);

        $this->assertEquals($result, $data);
    }

    public function testFailedValidation()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn(null);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn();
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn(true);
        $modelProphecy->get('gr2o_patient_nr', 'key')->willReturn(false);
        $modelProphecy->get('gr2o_patient_nr', 'autoInsertNotEmptyValidator')->willReturn(null);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(null);


        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => null];

        $this->expectException(ModelValidationException::class);
        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);
    }

    public function testRemoveIdField()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn(null);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn();
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn(true);
        $modelProphecy->get('gr2o_patient_nr', 'key')->willReturn(false);
        $modelProphecy->get('gr2o_patient_nr', 'autoInsertNotEmptyValidator')->willReturn(null);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(null);
        $modelProphecy->getKeys()->willReturn(['id' => 'gr2o_patient_nr']);


        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => null];

        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);
        $this->assertEquals($result, $data);
    }

    public function testRemoveIdField2()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn(null);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn();
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn(true);
        $modelProphecy->get('gr2o_patient_nr', 'key')->willReturn(false);
        $modelProphecy->get('gr2o_patient_nr', 'autoInsertNotEmptyValidator')->willReturn(null);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(null);
        $modelProphecy->getKeys()->willReturn(['gr2o_patient_nr']);


        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => null];

        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);
        $this->assertEquals($result, $data);
    }

    public function testUnknownValidator()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn([
            NotEmpty::class,
        ]);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn('NotExistingValidator');
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn([]);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(null);

        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => 1];

        $this->expectException(\Exception::class);
        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);
    }

    public function testWeirdClassValidator()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn([
            NotEmpty::class,
        ]);

        $randomObject = new \ArrayObject();
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn($randomObject);
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn([]);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(null);

        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => 1];

        $this->expectException(\Exception::class);
        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);
    }

    public function testRequiredNoInsertValidator()
    {
        $modelProphecy = $this->getPatientModel();
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn(null);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn();
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn(true);
        $modelProphecy->get('gr2o_patient_nr', 'key')->willReturn(false);
        $modelProphecy->get('gr2o_patient_nr', 'autoInsertNotEmptyValidator')->willReturn(false);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(null);


        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => 1];

        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);

        $this->assertEquals($result, $data);
    }

    public function testTableModel()
    {
        $modelProphecy = $this->prophesize(\MUtil_Model_TableModel::class);
        $modelProphecy->getCol(RespondentModel::SAVE_TRANSFORMER)->willReturn([]);
        $modelProphecy->getCol('validators')->willReturn([]);
        $modelProphecy->getCol('validator')->willReturn([]);
        $modelProphecy->getCol('required')->willReturn([]);
        $modelProphecy->getCol('type')->willReturn([]);
        $modelProphecy->getCol('default')->willReturn([]);
        $modelProphecy->getKeys()->willReturn([]);

        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => 1];

        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);

        $this->assertEquals($result, $data);
    }

    public function testSaveTransformerFields()
    {
        $modelProphecy = $this->prophesize(RespondentModel::class);
        $modelProphecy->getCol('table')->willReturn([
            'gr2o_patient_nr' => 'gems__respondent2org',
            'gr2o_created' => 'gems__respondent2org',
            'gr2o_created_by' => 'gems__respondent2org',
            //'grs_id_user' => 'gems__respondents'
        ]);
        $modelProphecy->get('gr2o_patient_nr', 'validators')->willReturn(null);
        $modelProphecy->get('gr2o_patient_nr', 'validator')->willReturn();
        $modelProphecy->get('gr2o_patient_nr', 'required')->willReturn(true);
        $modelProphecy->get('gr2o_patient_nr', 'key')->willReturn(false);
        $modelProphecy->get('gr2o_patient_nr', 'autoInsertNotEmptyValidator')->willReturn(false);
        $modelProphecy->has('gr2o_patient_nr', 'type')->willReturn(null);

        $modelProphecy->get('gr2o_created', 'validators')->willReturn(null);
        $modelProphecy->get('gr2o_created', 'validator')->willReturn();
        $modelProphecy->get('gr2o_created', 'required')->willReturn(true);
        $modelProphecy->get('gr2o_created', 'key')->willReturn(false);
        $modelProphecy->get('gr2o_created', 'autoInsertNotEmptyValidator')->willReturn(false);
        $modelProphecy->has('gr2o_created', 'type')->willReturn(null);

        $modelProphecy->get('gr2o_created_by', 'validators')->willReturn(null);
        $modelProphecy->get('gr2o_created_by', 'validator')->willReturn();
        $modelProphecy->get('gr2o_created_by', 'required')->willReturn(true);
        $modelProphecy->get('gr2o_created_by', 'key')->willReturn(false);
        $modelProphecy->get('gr2o_created_by', 'autoInsertNotEmptyValidator')->willReturn(false);
        $modelProphecy->has('gr2o_created_by', 'type')->willReturn(null);

        $modelProphecy->getCol(RespondentModel::SAVE_TRANSFORMER)->willReturn([
            'gr2o_created_by' => 1,
            'gr2o_created' => true,
        ]);
        $modelProphecy->getCol('default')->willReturn([]);
        $modelProphecy->getJoinFields()->willReturn([]);
        $modelProphecy->getSaveTables()->willReturn([
            'gems__respondent2org' => true,
            'gems__respondents' => true,
        ]);
        $modelProphecy->getKeys()->willReturn([]);

        $transformer = $this->getTransformer();

        $data = ['gr2o_patient_nr' => 1];

        $result = $transformer->transformRowBeforeSave($modelProphecy->reveal(), $data);

        $this->assertEquals($result, $data);
    }

    protected function getPatientModel($unrevealed = false)
    {
        $modelProphecy = $this->prophesize(RespondentModel::class);
        $modelProphecy->getCol('table')->willReturn([
            'gr2o_patient_nr' => 'gems__respondent2org',
            //'g2o_id_user' => 'gems__respondent2org',
            //'grs_id_user' => 'gems__respondents'
        ]);
        $modelProphecy->getCol(RespondentModel::SAVE_TRANSFORMER)->willReturn([]);
        $modelProphecy->getCol('default')->willReturn([]);
        $modelProphecy->getJoinFields()->willReturn([]);
        $modelProphecy->getSaveTables()->willReturn([
            'gems__respondent2org' => true,
            'gems__respondents' => true,
        ]);
        $modelProphecy->getKeys()->willReturn([]);

        return $modelProphecy;
    }

    protected function getTransformer()
    {
        $overloaderProphecy = $this->prophesize(ProjectOverloader::class);
        $overloaderProphecy->create('Validate_NotEmpty')->willReturn(new \Zend_Validate_NotEmpty());
        $overloaderProphecy->create('Validate_Float')->willReturn(new \Zend_Validate_Float());
        $overloaderProphecy->create('Validate_Date', Argument::cetera())->willReturn(new \Zend_Validate_Date());
        $overloaderProphecy->create('Validate_Date')->willReturn(new \Zend_Validate_Date());
        $overloaderProphecy->create('Validate_NotExistingValidator')->willReturn(null);

        return new ValidateFieldsTransformer($overloaderProphecy->reveal(), 1);
    }

}
