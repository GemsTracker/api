<?php


namespace Gems\Rest\Fhir\Model\Transformer;


class TreatmentStatusTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public static $reverseAppointmentStatusTranslation = [
        'active' => 'AC',
        'revoked' => ['AB', 'CA'],
        'completed' => 'CO',
    ];

    public static $reverseTrackStatusTranslation = [
        'active' => 'OK',
        // 'completed' => null, // Manual filter!
        'entered-in-error' => 'mistake',
        'revoked' => [
            'retract',
            'stop',
            'refused',
            'misdiag',
            'diagchange',
            'agenda_cancelled',
            'incap',
        ],
    ];



    public $statusField = 'status';

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
        if (isset($filter[$this->statusField])) {
            $reversedAppointmentStatusTranslations = self::$reverseAppointmentStatusTranslation;

            $appointmentStatus = $this->translateStatusField($filter[$this->statusField], $reversedAppointmentStatusTranslations);

            $trackStatus = $this->translateStatusField($filter[$this->statusField], self::$reverseTrackStatusTranslation);

            $trackStatementCompleted = null;
            if ($filter[$this->statusField] == 'completed' ||
                (is_array($filter[$this->statusField]) && in_array('completed', $filter[$this->statusField]))) {
                $trackStatementCompleted = 'gr2t_completed >= gr2t_count OR gr2t_end_date >= NOW()';
            }

            $appointmentStatement = null;
            if ($appointmentStatus !== null) {
                $appointmentStatement = 'ap2.gap_status ';
                if (count($appointmentStatus) > 1) {
                    $appointmentStatement .= ' IN (\'' . join('\', \'', $appointmentStatus) . '\')';
                } else {
                    $firstStatus = reset($appointmentStatus);
                    $appointmentStatement .= ' = \'' . $firstStatus . '\'';
                }
            }

            $trackStatement = null;
            if ($trackStatus !== null) {
                $trackStatement = 'gr2t_reception_code ';
                if (count($trackStatus) > 1) {
                    $trackStatement .= ' IN (\'' . join('\', \'', $trackStatus) . '\')';
                } else {
                    $firstStatus = reset($trackStatus);
                    $trackStatement .= ' = \'' . $firstStatus . '\'';
                }
            }
            if ($trackStatementCompleted !== null) {
                if ($trackStatement === null) {
                    $trackStatement = $trackStatementCompleted;
                } else {
                    $trackStatement = '('. $trackStatement . ' OR ' .  $trackStatementCompleted . ')';
                }
            }

            if ($appointmentStatement !== null && $trackStatement !== null) {
                $filter[] = new \Zend_Db_Expr('CASE
                    WHEN pt2.ptr_id_treatment THEN '.$appointmentStatement.'
                    WHEN pt1.ptr_id_treatment THEN '.$trackStatement.'
                END');
            } elseif ($appointmentStatement !== null) {
                $filter[] = $appointmentStatement;
            } elseif ($trackStatement !== null) {
                $filter[] = $trackStatement;
            }

            unset($filter[$this->statusField]);
        }

        return $filter;
    }

    protected function translateStatusField($originalStatus, $translations)
    {
        $translatedStatus = null;
        if (is_array($originalStatus)) {
            $translatedStatus = [];
            foreach ($originalStatus as $key => $status) {
                if (isset($translations[$status])) {
                    if (is_array($translations[$status])) {
                        $translatedStatus = array_merge($translatedStatus, $translations[$status]);
                    } else {
                        $translatedStatus[] = $translations[$status];
                    }
                }
            }
        } elseif (isset($translations[$originalStatus])) {
            $translatedStatus = [$translations[$originalStatus]];
        }

        return $translatedStatus;
    }
}
