<?php

namespace Ichom\Model\Transformer;


class MedicalCategoryReferenceTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var string
     */
    protected $medicalCategoryIdField;

    public function __construct($medicalCategoryIdField)
    {
        $this->medicalCategoryIdField = $medicalCategoryIdField;
    }

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['medicalCategory'])) {
            $filter[$this->medicalCategoryIdField] = $filter['medicalCategory'];
            unset($filter['medicalCategory']);
        }

        return $filter;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $rowIndex=>$row) {
            if (isset($row[$this->medicalCategoryIdField])) {
                $medicalCategoryReference = [
                    'id' => (int)$row[$this->medicalCategoryIdField],
                    'reference' => 'ichom/medical-category/' . $row[$this->medicalCategoryIdField],
                ];

                if (isset($row['gmdc_id_medical_category'])) {
                    $medicalCategoryReference['name'] = $row['gmdc_name'];
                    $medicalCategoryReference['name'] = $row['gmdc_name'];
                }

                $data[$rowIndex]['medicalCategory'] = $medicalCategoryReference;
            }
        }

        return $data;
    }
}
