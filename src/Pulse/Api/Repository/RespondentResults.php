<?php


namespace Pulse\Api\Repository;


class RespondentResults
{
    protected $dbFieldToNormField = [
        'gno_field_1' => 'treatment',
        'gno_field_2' => 'caretaker',
        'gno_field_3' => 'location',
    ];

    protected $normFieldToDbField;

    public function getDbField($normField)
    {
        if (empty($this->normFieldToDbField)) {
            $this->normFieldToDbField = array_flip($this->dbFieldToNormField);
        }

        if (isset($this->normFieldToDbField[$normField])) {
            return $this->normFieldToDbField[$normField];
        }

        return false;
    }
}