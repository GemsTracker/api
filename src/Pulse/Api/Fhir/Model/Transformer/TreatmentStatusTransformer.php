<?php


namespace Pulse\Api\Fhir\Model\Transformer;


use Gems\Rest\Fhir\Model\TreatmentModel;

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
            } elseif ($this->modelType === TreatmentModel::RESPONDENTTRACKMODEL) {
                if (!is_array($filter[$this->statusField])) {
                    $filter[$this->statusField] = [$filter[$this->statusField]];
                }

                $trackStatements = [];

                foreach($filter[$this->statusField] as $status) {
                    switch($status) {
                        case 'active':
                            $trackStatements[] = '(gr2t_reception_code = \'OK\' AND gr2t_completed < gr2t_count AND (gr2t_end_date IS NULL OR gr2t_end_date < NOW()))';
                            break;
                        case 'completed':
                            $trackStatements[] = '(gr2t_reception_code = \'OK\' AND (gr2t_completed >= gr2t_count OR gr2t_end_date >= NOW()))';
                            break;
                        case 'entered-in-error':
                            $trackStatements[] = '(gr2t_reception_code = \'mistake\')';
                            break;
                        case 'revoked':

                            $trackStatus = [
                                'retract',
                                'stop',
                                'refused',
                                'misdiag',
                                'diagchange',
                                'agenda_cancelled',
                                'incap',
                            ];

                            $trackStatements[] = '(gr2t_reception_code IN (\'' . join('\', \'', $trackStatus) . '\'))';
                            break;
                    }
                }

                if (count($trackStatements)) {
                    $filter[] = $trackStatements;
                }
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
