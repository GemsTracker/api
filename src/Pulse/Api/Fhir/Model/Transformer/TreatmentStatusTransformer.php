<?php


namespace Pulse\Api\Fhir\Model\Transformer;


use Pulse\Api\Fhir\Model\TreatmentModel;

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
     * @var string|null
     */
    protected $modelType;

    public function __construct($modelType = null)
    {
        $this->modelType = $modelType;
    }

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
            if ($this->modelType === TreatmentModel::APPOINTMENTMODEL) {
                $appointmentStatus = $this->translateStatusField($filter[$this->statusField], self::$reverseAppointmentStatusTranslation);

                $appointmentStatement = null;
                if ($appointmentStatus !== null) {
                    $appointmentStatement = 'gap_status ';
                    if (count($appointmentStatus) > 1) {
                        $appointmentStatement .= ' IN (\'' . join('\', \'', $appointmentStatus) . '\')';
                    } else {
                        $firstStatus = reset($appointmentStatus);
                        $appointmentStatement .= ' = \'' . $firstStatus . '\'';
                    }
                }
                if ($appointmentStatement !== null) {
                    $filter[] = $appointmentStatement;
                }
                unset($filter[$this->statusField]);
            } elseif ($this->modelType === TreatmentModel::RESPONDENTTRACKMODEL) {
                if ($this->statusField !== 'status') {
                    $filter['status'] = $filter[$this->statusField];
                    unset($this->statusField);
                }
            }
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
