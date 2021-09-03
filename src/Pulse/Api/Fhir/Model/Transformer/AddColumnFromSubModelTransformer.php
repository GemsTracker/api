<?php

namespace Pulse\Api\Fhir\Model\Transformer;


class AddColumnFromSubModelTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var array
     */
    protected $singleValueColumns;

    /**
     * Name of the destination column
     *
     * @var string
     */
    protected $columnMap;

    /**
     * Name of the submodel
     *
     * @var string
     */
    protected $subModelName;

    public function __construct($subModelName, array $columnMap, array $singleValueColumns = [])
    {
        $this->singleValueColumns = $singleValueColumns;
        $this->columnMap = $columnMap;
        $this->subModelName = $subModelName;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $rowKey => $row) {
            foreach($this->columnMap as $columnName => $subColumnName) {
                if (isset($row[$this->subModelName])) {
                    if (!in_array($columnName, $this->singleValueColumns)) {
                        $data[$rowKey][$columnName] = array_filter(array_column($row[$this->subModelName], $subColumnName), function ($value) {
                            return $value !== null && $value !== '';
                        });
                    } else {
                        $firstRow = reset($row[$this->subModelName]);
                        $data[$rowKey][$columnName] = $firstRow[$subColumnName];
                    }
                }
            }

        }
        return $data;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        $subModelRows = [];
        $multi = true;
        foreach($this->columnMap as $columnName => $subColumnName) {
            if (isset($row[$columnName])) {
                if (!in_array($columnName, $this->singleValueColumns)) {
                    if (count($row[$columnName])) {
                        if (count($row[$columnName]) > 1 && $multi) {
                            foreach ($row[$columnName] as $subValue) {
                                $subModelRows[] = [
                                    $subColumnName => $subValue,
                                ];
                            }
                        } else {
                            $multi = false;
                            if (!count($subModelRows)) {
                                $subModelRows[] = [];
                            }
                            $subModelRows[0][$subColumnName] = reset($row[$columnName]);
                        }
                    }
                }
            }
        }

        foreach($this->columnMap as $columnName => $subColumnName) {
            if (isset($row[$columnName]) && in_array($columnName, $this->singleValueColumns)) {
                foreach($subModelRows as $subrowKey => $subModelRow) {
                    $subModelRows[$subrowKey][$subColumnName] = $row[$columnName];
                }
            }
        }

        $row[$this->subModelName] = $subModelRows;

        return $row;
    }
}
