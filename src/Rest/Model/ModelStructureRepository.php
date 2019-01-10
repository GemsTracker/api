<?php


namespace Gems\Rest\Model;


class ModelStructureRepository
{
    protected $apiNames;

    /**
     * @var \MUtil_Model_ModelAbstract
     */
    private $model;

    protected $reverseApiNames;

    protected $routeOptions;

    protected $structure;



    public function __construct(\MUtil_Model_ModelAbstract $model)
    {
        $this->model = $model;
    }

    /**
     * Filter the columns of a row with routeoptions like allowed_fields, disallowed_fields and readonly_fields
     *
     * @param $row Row with model values
     * @param bool $save Will the row be saved after filter (enables readonly
     * @param bool $useKeys Use keys or values in the filter of the row
     * @return array Filtered array
     */
    protected function filterColumns($row, $save=false, $useKeys=true)
    {
        $row = RouteOptionsModelFilter::filterColumns($row, $this->routeOptions, $save, $useKeys);

        return $row;
    }

    /**
     * Get the api column names translations if they are set
     *
     * @param bool $reverse return the reversed translations
     * @return array|null
     */
    protected function getApiNames($reverse=false)
    {
        if (!$this->apiNames) {
            $this->apiNames = $this->getApiSubModelNames($this->model);
        }

        if ($reverse) {
            if (!$this->reverseApiNames) {
                $this->reverseApiNames = $this->flipMultiArray($this->apiNames);
            }
            return $this->reverseApiNames;
        }

        return $this->apiNames;
    }

    protected function getApiSubModelNames($model)
    {
        $apiNames = $this->model->getCol('apiName');

        $subModels = $model->getCol('model');
        foreach($subModels as $subModelName=>$subModel) {
            $apiNames[$subModelName] = $this->getApiSubModelNames($subModel);
        }
        return $apiNames;
    }

    /**
     * Get the structural information of each model field so it will be easier to see what information is
     * received or needed for a POST/PATCH
     *
     * @return array
     * @throws \Zend_Date_Exception
     */
    public function getStructure()
    {
        if (!$this->structure) {
            $columns = $this->model->getItemNames();

            $translations = $this->getApiNames();

            $structureAttributes = [
                'label',
                'description',
                'required',
                'size',
                'maxlength',
                'type',
                'multiOptions',
                'default',
            ];

            $structure = [];

            $columns = $this->filterColumns($columns, false, false);

            foreach ($columns as $columnName) {

                $columnLabel = $columnName;
                if (isset($translations[$columnName]) && !empty($translations[$columnName])) {
                    $columnLabel = $translations[$columnName];
                }

                foreach ($structureAttributes as $attributeName) {
                    if ($this->model->has($columnName, $attributeName)) {

                        $propertyValue = $this->model->get($columnName, $attributeName);

                        $structure[$columnLabel][$attributeName] = $propertyValue;

                        if ($attributeName === 'type') {
                            switch ($structure[$columnLabel][$attributeName]) {
                                case 0:
                                    $structure[$columnLabel][$attributeName] = 'no_value';
                                    break;
                                case 1:
                                    $structure[$columnLabel][$attributeName] = 'string';
                                    break;
                                case 2:
                                    $structure[$columnLabel][$attributeName] = 'numeric';
                                    break;
                                case 3:
                                    $structure[$columnLabel][$attributeName] = 'date';
                                    break;
                                case 4:
                                    $structure[$columnLabel][$attributeName] = 'datetime';
                                    break;
                                case 5:
                                    $structure[$columnLabel][$attributeName] = 'time';
                                    break;
                                case 6:
                                    $structure[$columnLabel][$attributeName] = 'child_model';
                                    break;
                                default:
                                    $structure[$columnLabel][$attributeName] = 'no_value';
                                    break;
                            }
                        }

                        if ($attributeName == 'default') {
                            switch (true) {
                                case $structure[$columnLabel][$attributeName] instanceof \Zend_Db_Expr:
                                    $structure[$columnLabel][$attributeName] = $structure[$columnLabel][$attributeName]->__toString();
                                    break;
                                case ($structure[$columnLabel][$attributeName] instanceof \MUtil_Date
                                    && $structure[$columnLabel][$attributeName] == new \MUtil_Date):
                                    $structure[$columnLabel][$attributeName] = 'NOW()';
                                    break;
                                case ($structure[$columnLabel][$attributeName] instanceof \Zend_Date
                                    && $structure[$columnLabel][$attributeName] == new \Zend_Date):
                                    $structure[$columnLabel][$attributeName] = 'NOW()';
                                    break;
                                case is_object($structure[$columnLabel][$attributeName]):
                                    $structure[$columnLabel][$attributeName] = null;
                            }
                        }
                    }
                }
                if (isset($structure[$columnLabel])) {
                    $structure[$columnLabel]['name'] = $columnLabel;
                }
            }
            $this->structure = $structure;
        }

        return $this->structure;
    }
}