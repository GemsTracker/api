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

    public static $reverseStatusTranslation = [
        'booked' => 'AC',
        'cancelled' => ['AB', 'CA'],
        'fulfilled' => 'CO',
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
        $reversedStatusTranslations = self::$reverseStatusTranslation;

        if (isset($filter[$this->statusField])) {
            if (is_array($filter[$this->statusField])) {
                $translatedStatus = [];
                foreach ($filter[$this->statusField] as $key => $status) {
                    if (isset($reversedStatusTranslations[$status])) {
                        if (is_array($reversedStatusTranslations[$status])) {
                            $translatedStatus += $reversedStatusTranslations[$status];
                        } else {
                            $translatedStatus[] = $reversedStatusTranslations[$status];
                        }
                    }
                }
                if (count($translatedStatus)) {
                    $filter[$this->statusField] = $translatedStatus;
                    if (in_array('CO', $translatedStatus) && !in_array('AC', $translatedStatus)) {
                        if (count($translatedStatus) === 1) {
                            $filter[] = "($this->statusField = 'CO' OR ($this->statusField = 'AC' AND gap_admission_time < NOW()))";
                            unset($filter[$this->statusField]);
                        } else {
                            $statusString = join(',', array_map(function($status) {
                                return "'$status'";
                            }, $translatedStatus));
                            $filter[] = "($this->statusField IN ($statusString) OR ($this->statusField = 'AC' AND gap_admission_time < NOW()))";
                            unset($filter[$this->statusField]);
                        }
                    }
                    if (in_array('AC', $translatedStatus) && !in_array('CO', $translatedStatus)) {
                        if (count($translatedStatus) === 1) {
                            $filter[] = 'gap_admission_time > NOW()';
                        } else {
                            if (($acceptedKey = array_search('AC', $translatedStatus)) !== false) {
                                unset($translatedStatus[$acceptedKey]);
                            }
                            $statusString = join(',', array_map(function($status) {
                                return "'$status'";
                            }, $translatedStatus));
                            $filter[] = "($this->statusField IN ($statusString) OR ($this->statusField = 'AC' AND gap_admission_time > NOW()))";
                            unset($filter[$this->statusField]);
                        }
                    }
                } else {
                    unset($filter[$this->statusField]);
                }
            } elseif (isset($reversedStatusTranslations[$filter[$this->statusField]])) {
                $filter[$this->statusField] = $reversedStatusTranslations[$filter[$this->statusField]];
                if ($filter[$this->statusField] === 'CO') {
                    $filter[] = "($this->statusField = 'CO' OR ($this->statusField = 'AC' AND gap_admission_time < NOW()))";
                    unset($filter[$this->statusField]);
                } if ($filter[$this->statusField] === 'AC') {
                    $filter[] = 'gap_admission_time > NOW()';
                }
            }
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
            // Assume fulfilled when appointment is in the past
            if ($item[$this->statusField] === 'AC' || $item[$this->statusField] === 'booked') {
                $admissionTime = new \DateTimeImmutable($item['gap_admission_time']);
                $now = new \DateTimeImmutable();
                if ($admissionTime < $now) {
                    $item[$this->statusField] = 'CO';
                }
            }
            if (isset($item[$this->statusField]) && isset(self::$statusTranslation[$item[$this->statusField]])) {
                $data[$key][$this->statusField] = self::$statusTranslation[$item[$this->statusField]];
            }
        }

        return $data;
    }
}