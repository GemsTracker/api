<?php

namespace Pulse\Api\Emma\Fhir\Model\Transformer;

/**
 * Transform other fields
 */
class PatientOtherFieldsTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $genderTranslations = [
        'male' => 'M',
        'female' => 'F',
        'unknown' => 'U',
    ];

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        // Gender
        $row['grs_gender'] = 'U';
        if (isset($row['gender'])) {
            if (isset($this->genderTranslations[$row['gender']])) {
                $row['grs_gender'] = $this->genderTranslations[$row['gender']];
            }
        }

        // Birthday
        if (isset($row['birthDate'])) {
            $birthDate = \DateTime::createFromFormat('Y-m-d', $row['birthDate']);
            if ($birthDate !== false) {
                $legacyDate = new \MUtil_Date($birthDate);
                $row['grs_birthday'] = $legacyDate;
            }
        }

        return $row;
    }
}
