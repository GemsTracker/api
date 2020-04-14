<?php


namespace Gems\Rest\Fhir\Model\Transformer;


class AppointmentStatusTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public static $statusTranslation = [
        'AB' => 'cancelled',
        'AC' => 'booked',
        'CA' => 'cancelled',
        'CO' => 'fulfilled',
    ];

    public $statusField = 'gap_status';

    /**
     * This transform function checks the filter for
     * a) retreiving filters to be applied to the transforming data,
     * b) adding filters that are needed
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $filter
     * @return array The (optionally changed) filter
     */
    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        $reversedStatusTranslations = array_flip(self::$statusTranslation);

        if (isset($filter[$this->statusField], $reversedStatusTranslations[$filter[$this->statusField]])) {
            $filter[$this->statusField] = $reversedStatusTranslations[$filter[$this->statusField]];
        }

        return $filter;
    }

    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param \MUtil_Model_ModelAbstract $model The parent model
     * @param array $data Nested array
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key=>$item) {
            if (isset($item[$this->statusField]) && isset(self::$statusTranslation[$item[$this->statusField]])) {
                $data[$key][$this->statusField] = self::$statusTranslation[$item[$this->statusField]];
            }
        }

        return $data;
    }
}
