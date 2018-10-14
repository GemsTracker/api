<?php


namespace Gems\Rest\Model;


use Zalt\Loader\ProjectOverloader;

class ModelProcessor
{
    protected $errors;

    protected $idField;

    /**
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    protected $requiredFields;

    protected $update;

    protected $validators;

    public function __construct(ProjectOverloader $loader, \MUtil_Model_ModelAbstract $model, $userId)
    {
        $this->loader = $loader;
        $this->model = $model;
        $this->userId = $userId;
    }

    /**
     * Get the id field of the model if it is set in the model keys
     *
     * @return Fieldname
     */
    protected function getIdField()
    {
        if (!$this->idField) {
            $keys = $this->model->getKeys();
            if (isset($keys['id'])) {
                $this->idField = $keys['id'];
            }
        }

        return $this->idField;
    }

    /**
     * Get a specific validator to be run during validation
     *
     * @param $validator
     * @param null $options
     * @return object
     * @throws \Zalt\Loader\Exception\LoadException
     */
    public function getValidator($validator, $options=null)
    {
        if ($validator instanceof \Zend_Validate_Interface) {
            return $validator;
        } elseif (is_string($validator)) {
            $validatorName = $validator;
            if ($options !== null) {
                $validator = $this->loader->create('Validate_' . $validator, $options);
            } else {
                $validator = $this->loader->create('Validate_'.$validator);
            }

            if ($validator) {
                return $validator;
            } else {
                throw new \Exception(sprintf('Validator %s not found', $validatorName));
            }
        } else {
            throw new \Exception(
                sprintf(
                    'Invalid validator provided to addValidator; must be string or Zend_Validate_Interface. Supplied %s',
                    gettype($validator)
                )
            );
        }
    }

    /**
     * Get the validators for each of the columns in the model
     * This function will also create required validators and type validators for rows that are required.
     * If a POST method is used, the key values will be excluded
     *
     * @return array
     * @throws \Zalt\Loader\Exception\LoadException
     */
    public function getValidators()
    {
        if (!$this->validators) {
            if ($this->model instanceof \MUtil_Model_JoinModel && method_exists($this->model, 'getSaveTables')) {
                $saveableTables = $this->model->getSaveTables();

                $multiValidators = [];
                $singleValidators = [];
                $allRequiredFields = [];
                $types = [];

                foreach($this->model->getCol('table') as $colName=>$table) {
                    if (isset($saveableTables[$table])) {
                        $columnValidators = $this->model->get($colName, 'validators');
                        if ($columnValidators !== null) {
                            $multiValidators[$colName] = $columnValidators;
                        }
                        $columnValidator = $this->model->get($colName, 'validator');
                        if ($columnValidator) {
                            $singleValidators[$colName] = $columnValidator;
                        }
                        $columnRequired = $this->model->get($colName, 'required');
                        if ($columnRequired === true) {
                            if ($this->update === true || $this->model->get($colName, 'key') !== true) {
                                $allRequiredFields[$colName] = $columnRequired;
                            }
                        }
                        if ($columnType = $this->model->get($colName, 'type')) {
                            $types[$colName] = $this->model->get($colName, 'type');
                        }
                    }
                }
            } else {
                $multiValidators = $this->model->getCol('validators');
                $singleValidators = $this->model->getCol('validator');
                $allRequiredFields = $this->model->getCol('required');

                $types = $this->model->getCol('type');
            }

            $defaultFields = $this->model->getCol('default');

            $model = $this->model;
            $saveTransformers = $this->model->getCol($model::SAVE_TRANSFORMER);

            $changeFields = [];
            foreach($saveTransformers as $columnName=>$value) {
                if (substr_compare( $columnName, '_by', -3 ) === 0 && $value == $this->userId) {
                    $changeFields[$columnName] = true;
                    $withoutBy = str_replace('_by', '', $columnName);
                    if (isset($saveTransformers[$withoutBy])) {
                        $changeFields[$withoutBy] = true;
                    }
                }
            }

            $joinFields = [];
            if ($this->model instanceof \MUtil_Model_JoinModel) {
                $joinFields = array_flip($this->model->getJoinTables());
            }

            $requiredFields = array_diff_key($allRequiredFields, $defaultFields, $changeFields, $joinFields);

            $this->requiredFields = $requiredFields;

            foreach($multiValidators as $columnName=>$validators) {
                foreach($validators as $key=>$validator) {
                    $multiValidators[$columnName][$key] = $this->getValidator($validator);
                }
            }

            foreach($singleValidators as $columnName=>$validator) {
                $multiValidators[$columnName][] = $this->getValidator($validator);
            }

            foreach($requiredFields as $columnName=>$required) {

                if ($required && $this->model->get($columnName, 'autoInsertNotEmptyValidator') !== false) {
                    $multiValidators[$columnName][] = $this->getValidator('NotEmpty');

                } else {
                    $this->requiredFields[$columnName] = false;
                }

                if (!isset($multiValidators[$columnName]) || count($multiValidators[$columnName]) === 1) {
                    switch ($types[$columnName]) {
                        case \MUtil_Model::TYPE_STRING:
                            //$multiValidators[$columnName][] = $this->getValidator('Alnum', ['allowWhiteSpace' => true]);
                            break;

                        case \MUtil_Model::TYPE_NUMERIC:
                            $multiValidators[$columnName][] = $this->getValidator('Float');
                            break;

                        case \MUtil_Model::TYPE_DATE:
                            $multiValidators[$columnName][] = $this->getValidator('Date');
                            break;

                        case \MUtil_Model::TYPE_DATETIME:
                            $multiValidators[$columnName][] = $this->getValidator('Date', ['format' => \Zend_Date::ISO_8601]);
                            break;
                    }
                }
            }

            $this->validators = $multiValidators;
        }

        return $this->validators;
    }

    public function save($row, $update=false)
    {
        $this->update = $update;
        $row = $this->validateRow($row);

        return $this->model->save($row);
    }

    /**
     * Validate a row before saving it to the model and store the errors in $this->errors
     *
     * @param $row
     * @throws \Zalt\Loader\Exception\LoadException
     */
    public function validateRow($row)
    {
        $rowValidators = $this->getValidators();
        $idField = $this->getIdField();

        // No ID field is needed when updating
        if ($this->update && !is_array($idField) && isset($rowValidators[$idField])) {
            unset($rowValidators[$idField]);
        }

        foreach ($rowValidators as $colName=>$validators) {
            $value = null;
            if (isset($row[$colName])) {
                $value = $row[$colName];
            }

            if (
                (null === $value || '' === $value) &&
                (!$this->requiredFields || !isset($this->requiredFields[$colName]) || !$this->requiredFields[$colName])
            ) {
                continue;
            }

            foreach($validators as $validator) {
                if (!$validator->isValid($value, $row)) {
                    if (!isset($this->errors[$colName])) {
                        $this->errors[$colName] = [];
                    }
                    $this->errors[$colName] += $validator->getMessages();//array_merge($this->errors[$colName], $validator->getMessages());
                }
            }
        }

        if ($this->errors) {
            throw new Exception('Validation Errors');
        }

        return $row;
    }
}