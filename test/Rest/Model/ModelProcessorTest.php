<?php


namespace GemsTest\Rest\Model;


use Gems\Rest\Model\ModelProcessor;
use Gems\Rest\Model\ModelValidationException;
use GemsTest\Rest\Test\ZendDbTestCase;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Zalt\Loader\ProjectOverloader;
use Zend\Validator\NotEmpty;

class ModelProcessorTest extends ZendDbTestCase
{
    protected $loadZendDb1 = true;

    protected function getDataSet()
    {
        $file = str_replace('.php', '.yml', __FILE__);
        return new YamlDataSet($file);
    }

    public function testGetValidator()
    {
        $processor = $this->getProcessor();
        $validator = new \Zend_Validate_NotEmpty();
        $expectedValidator = $processor->getValidator($validator);
        $this->assertInstanceOf(\Zend_Validate_Interface::class, $expectedValidator, 'Validator not instance of Zend Validator');
    }

    public function testGetValidatorFromNameWithOptions()
    {
        $processor = $this->getProcessor();

        $expectedValidator = $processor->getValidator('NotEmpty');
        $this->assertInstanceOf(\Zend_Validate_Interface::class, $expectedValidator, 'Validator not instance of Zend Validator');

        $expectedValidator = $processor->getValidator('NotEmpty', ['option1' => true]);
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

    public function testGetValidatorFromModel()
    {
        $model = new \Gems_Model_PlaceholderModel('test', ['name', 'email'], ['name' => 'test', 'email' => 'test@test.test']);
        $model->set('name', 'validator', 'NotEmpty');
        $processor = $this->getProcessor($model);

        $validators = $processor->getValidators();

        $this->assertNotEmpty($validators);
        $this->assertArrayHasKey('name', $validators);
        $this->assertCount(1, $validators);

        $this->assertInstanceOf(\Zend_Validate_NotEmpty::class, $validators['name'][0]);
    }

    public function testGetValidatorInMultiFromModel()
    {
        $model = new \Gems_Model_PlaceholderModel('test', ['name', 'email'], ['name' => 'test', 'email' => 'test@test.test']);
        $model->set('name', 'validators', 'NotEmpty');
        $processor = $this->getProcessor($model);

        $validators = $processor->getValidators();

        $this->assertNotEmpty($validators);
        $this->assertArrayHasKey('name', $validators);
        $this->assertCount(1, $validators);

        $this->assertInstanceOf(\Zend_Validate_NotEmpty::class, $validators['name'][0]);
    }

    public function testGetValidatorsFromModel()
    {
        $model = new \Gems_Model_PlaceholderModel('test', ['name', 'email'], ['name' => 'test', 'email' => 'test@test.test']);
        $model->set('name', 'validators', ['NotEmpty', 'Alpha']);
        $processor = $this->getProcessor($model);

        $validators = $processor->getValidators();

        $this->assertNotEmpty($validators);
        $this->assertArrayHasKey('name', $validators);
        $this->assertCount(1, $validators);

        $this->assertInstanceOf(\Zend_Validate_NotEmpty::class, $validators['name'][0]);
        $this->assertInstanceOf(\Zend_Validate_Alpha::class, $validators['name'][1]);
    }

    public function testGetValidatorsInSingleFromModel()
    {
        $model = new \Gems_Model_PlaceholderModel('test', ['name', 'email'], ['name' => 'test', 'email' => 'test@test.test']);
        $model->set('name', 'validator', ['NotEmpty', 'Alpha']);
        $processor = $this->getProcessor($model);

        $validators = $processor->getValidators();

        $this->assertNotEmpty($validators);
        $this->assertArrayHasKey('name', $validators);
        $this->assertCount(1, $validators);

        $this->assertInstanceOf(\Zend_Validate_NotEmpty::class, $validators['name'][0]);
        $this->assertInstanceOf(\Zend_Validate_Alpha::class, $validators['name'][1]);
    }

    public function testGetRequiredValidatorFromModel()
    {
        $model = new \Gems_Model_PlaceholderModel('test', ['name', 'email'], ['name' => 'test', 'email' => 'test@test.test']);
        $model->set('name', 'required', true);
        $processor = $this->getProcessor($model);

        $validators = $processor->getValidators();

        $this->assertNotEmpty($validators);
        $this->assertArrayHasKey('name', $validators);
        $this->assertCount(1, $validators);

        $this->assertInstanceOf(\Zend_Validate_NotEmpty::class, $validators['name'][0]);
    }

    public function testGetRequiredValidatorWithTypesFromModel()
    {
        $model = new \Gems_Model_PlaceholderModel('test',
            ['name', 'email', 'score', 'today', 'now'],
            ['name' => 'test', 'email' => 'test@test.test', 'score' => 10,
                'today' => '2018-12-05', 'now' => '2018-12-05 15:13:00']
        );
        $model->set('name', 'required', true, 'type', \MUtil_Model::TYPE_STRING);
        $model->set('score', 'required', true, 'type', \MUtil_Model::TYPE_NUMERIC);
        $model->set('today', 'required', true, 'type', \MUtil_Model::TYPE_DATE);
        $model->set('now', 'required', true, 'type', \MUtil_Model::TYPE_DATETIME);

        $processor = $this->getProcessor($model);

        $validators = $processor->getValidators();

        $this->assertNotEmpty($validators);
        $this->assertArrayHasKey('name', $validators);
        $this->assertCount(4, $validators);
        $this->assertCount(1, $validators['name']);
        $this->assertCount(2, $validators['score']);
        $this->assertCount(2, $validators['today']);
        $this->assertCount(2, $validators['now']);



        $this->assertInstanceOf(\Zend_Validate_NotEmpty::class, $validators['name'][0]);
        $this->assertInstanceOf(\Zend_Validate_NotEmpty::class, $validators['score'][0]);
        $this->assertInstanceOf(\Zend_Validate_Float::class, $validators['score'][1]);
        $this->assertInstanceOf(\Zend_Validate_NotEmpty::class, $validators['today'][0]);
        $this->assertInstanceOf(\Zend_Validate_Date::class, $validators['today'][1]);
        $this->assertInstanceOf(\Zend_Validate_NotEmpty::class, $validators['now'][0]);
        $this->assertInstanceOf(\Zend_Validate_Date::class, $validators['now'][1]);
    }

    public function testGetDoNotSetRequiredValidatorFromModel()
    {
        $model = new \Gems_Model_PlaceholderModel('test', ['name', 'email'], ['name' => 'test', 'email' => 'test@test.test']);
        $model->set('name', 'required', true, 'autoInsertNotEmptyValidator', false);
        $processor = $this->getProcessor($model);

        $validators = $processor->getValidators();

        $this->assertEmpty($validators);
    }

    public function testRemoveChangeFieldsFromValidationModel()
    {
        $model = new \Gems_Model_PlaceholderModel(
            'test',
            ['name', 'email', 'opened', 'opened_by'],
            ['name' => 'test', 'email' => 'test@test.test', 'openend' => '2018-12-05 00:00:00', 'opened_by' => 1]);

        $model->set('opened', 'required', true);
        $model->set('opened_by', 'required', true);
        $model->setOnSave('opened', new \MUtil_Db_Expr_CurrentTimestamp());
        $model->setSaveOnChange('opened');
        $model->setOnSave('opened_by', 1);
        $model->setSaveOnChange('opened_by');
        $processor = $this->getProcessor($model);

        $validators = $processor->getValidators();

        $this->assertEmpty($validators);
    }

    public function testValidatorsOfJoinModel()
    {
        $model = $this->getRespondentModel();

        $processor = $this->getProcessor($model);

        $validators = $processor->getValidators();

        $this->assertCount(4, $validators);
        $this->assertNotEmpty($validators);
        $this->assertArrayHasKey('grs_first_name', $validators);
        $this->assertArrayHasKey('grs_last_name', $validators);
        $this->assertArrayHasKey('gr2o_id_user', $validators);
        $this->assertArrayHasKey('gr2o_patient_nr', $validators);

        $this->assertCount(1, $validators['grs_first_name']);
        $this->assertCount(1, $validators['grs_last_name']);
        $this->assertCount(2, $validators['gr2o_id_user']);
        $this->assertCount(2, $validators['gr2o_patient_nr']);

        $this->assertInstanceOf(\Zend_Validate_Alpha::class, $validators['grs_first_name'][0]);
        $this->assertInstanceOf(\Zend_Validate_NotEmpty::class, $validators['grs_last_name'][0]);
        $this->assertInstanceOf(\Zend_Validate_NotEmpty::class, $validators['gr2o_id_user'][0]);
        $this->assertInstanceOf(\Zend_Validate_Float::class, $validators['gr2o_id_user'][1]);
        $this->assertInstanceOf(\Zend_Validate_Alnum::class, $validators['gr2o_patient_nr'][0]);
        $this->assertInstanceOf(\Zend_Validate_NotEmpty::class, $validators['gr2o_patient_nr'][1]);
    }

    public function testValidationSuccess()
    {
        $model = $this->getRespondentModel();

        $processor = $this->getProcessor($model);

        $newRow = [
            'grs_last_name' => 'Arendelle',
            'grs_first_name' => 'Anna',
            'gr2o_patient_nr' => 'T002',
            'gr2o_id_user' => 2,
        ];

        $row = $processor->validateRow($newRow);

        $this->assertEquals($newRow, $row);
    }

    public function testValidationFailure()
    {
        $model = $this->getRespondentModel();

        $processor = $this->getProcessor($model);

        $newRow = [
            'grs_first_name' => 'Anna',
        ];

        $this->expectException(ModelValidationException::class);
        $this->expectExceptionMessage('Errors were found when validating Gems_Model_JoinModel');
        $this->getExpectedException();
        $processor->validateRow($newRow);
    }

    public function testValdiationWithOneKey()
    {
        $model = new \MUtil_Model_TableModel('gems__organizations', 'organizations');
        $row = [
            'gor_name' => 'Test',
            'gor_code' => 'test',
        ];

        $processor = $this->getProcessor($model);
        $result = $processor->validateRow($row);

        $this->assertEquals($row, $result);
    }

    public function testValdiationWithNamedKey()
    {
        $model = new \MUtil_Model_TableModel('gems__organizations', 'organizations');
        $model->setKeys(['id1' => 'gor_id_organization']);
        $row = [
            'gor_name' => 'Test',
            'gor_code' => 'test',
        ];

        $processor = $this->getProcessor($model);
        $result = $processor->validateRow($row);

        $this->assertEquals($row, $result);
    }

    public function testSaveSuccess()
    {
        $model = new \MUtil_Model_TableModel('gems__organizations', 'organizations');
        $model->setKeys(['id1' => 'gor_id_organization']);
        $row = [
            'gor_name' => 'Test',
            'gor_code' => 'test',
        ];

        $processor = $this->getProcessor($model);
        $result = $processor->save($row);

        $expectedResult = $row + ['gor_id_organization' => 1];

        $this->assertEquals($expectedResult, $result);
    }

    public function testSaveAddDefaults()
    {
        $model = new \MUtil_Model_TableModel('gems__organizations', 'organizations');
        $model->setKeys(['id1' => 'gor_id_organization']);
        $row = [
            'gor_name' => 'Test',
            'gor_code' => 'test',
        ];

        $processor = $this->getProcessor($model);
        $processor->setAddDefaults(true);
        $result = $processor->save($row, false);

        $expectedResult = $row + ['gor_id_organization' => '1'];

        $this->assertEquals($expectedResult, $result);
    }

    public function testSaveJoinModel()
    {
        $model = $this->getRespondentModel();

        $newRow = [
            'grs_last_name' => 'Arendelle',
            'grs_first_name' => 'Anna',
            'gr2o_patient_nr' => 'T002',
            'gr2o_id_user' => 2,
            'gr2o_id_organization' => 1,
        ];

        $processor = $this->getProcessor($model);
        $processor->setAddDefaults(true);
        $result = $processor->save($newRow, false);

        // Only check the values of keys in the newRow, as there are a lot of added defaults
        $filteredResult = array_intersect_key($result, $newRow);
        $this->assertEquals($newRow, $filteredResult);
    }

    public function testSaveTimezoneDate()
    {
        $model = $this->getRespondentModel();
        $model->set('grs_birthday', 'type', \MUtil_Model::TYPE_DATE);

        $newRow = [
            'grs_last_name' => 'Arendelle',
            'grs_first_name' => 'Anna',
            'gr2o_patient_nr' => 'T002',
            'gr2o_id_user' => 2,
            'gr2o_id_organization' => 1,
            'grs_birthday' => '2000-01-01T00:00:00+02:00',
        ];

        $processor = $this->getProcessor($model);
        $processor->setAddDefaults(true);
        $result = $processor->save($newRow, false);

        $testBirthday = new \MUtil_Date('2000-01-01 00:00:00');
        $expectedResult = $newRow;
        $expectedResult['grs_birthday'] = $testBirthday;

        // Only check the values of keys in the newRow, as there are a lot of added defaults
        $filteredResult = array_intersect_key($result, $newRow);
        $this->assertEquals($expectedResult, $filteredResult);
    }

    protected function getRespondentModel()
    {
        $model = new \Gems_Model_JoinModel('respondents', 'gems__respondent2org', 'grs', true);
        $model->addTable('gems__respondents', ['gr2o_id_user' => 'grs_id_user'], 'gr2o', true);

        $model->set('grs_last_name', 'required', true);
        $model->set('grs_first_name', 'validator', 'Alpha');
        $model->set('gr2o_patient_nr', 'validators', ['Alnum', 'NotEmpty']);

        \Gems_Model::setChangeFieldsByPrefix($model, 'grs', 1);
        \Gems_Model::setChangeFieldsByPrefix($model, 'gr2o', 1);
        $model->setOnSave('gr2o_opened', new \MUtil_Db_Expr_CurrentTimestamp());
        $model->setSaveOnChange('gr2o_opened');
        $model->setOnSave('gr2o_opened_by', 1);
        $model->setSaveOnChange('gr2o_opened_by');

        return $model;
    }


    protected function getProcessor($model = null)
    {
        $notEmptyValidator = new \Zend_Validate_NotEmpty();
        $alphaValidator = new \Zend_Validate_Alpha();
        $alphaNumValidator = new \Zend_Validate_Alnum();
        $floatValidator = new \Zend_Validate_Float();
        $dateValidator = new \Zend_Validate_Date();


        $loader = $this->prophesize(ProjectOverloader::class);
        $loader->create('Validate_NotEmpty')->willReturn($notEmptyValidator);
        $loader->create('Validate_Alpha')->willReturn($alphaValidator);
        $loader->create('Validate_Alnum')->willReturn($alphaNumValidator);
        $loader->create('Validate_Float')->willReturn($floatValidator);
        $loader->create('Validate_Date')->willReturn($dateValidator);
        $loader->create('Validate_Date', Argument::type('array'))->willReturn($dateValidator);
        $loader->create('Validate_NotEmpty', Argument::type('array'))->willReturn($notEmptyValidator);
        $loader->create(Argument::any())->willReturn(null);

        if ($model === null) {
            $modelProphecy = $this->prophesize(\MUtil_Model_ModelAbstract::class);

            $model = $modelProphecy->reveal();
        }

        return new ModelProcessor($loader->reveal(), $model, 1);
    }
}