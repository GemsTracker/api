<?php


namespace Pulse\Api\Model\Emma;


use Pulse\Api\Model\ApiModelTranslator;

class AppointmentImportTranslator extends ApiModelTranslator
{
    /**
     * @var \Gems_Agenda
     */
    protected $agenda;

    /**
     * @var array Api translations for the respondent
     */
    public $translations = [
        'gap_id_in_source' => 'id',
        'gap_admission_time' => 'admission_time',
        'gap_status' => 'status',
        'gap_diagnosis_code' => 'diagnosis_code',
        'gap_id_episode' => 'episode_id',
        //'gap_dbc_id' => 'dbc_id',
        // 'location',
        //'' => 'attended_by',
        //'' => 'activity',
        //'' => 'room',
    ];

    public function __construct(\Gems_Agenda $agenda)
    {
        $this->agenda = $agenda;
        parent::__construct(null);
    }

    /**
     * Translate a row with api values
     *
     * @param $row
     * @param
     * @return array
     */
    public function translateRow($row, $reversed = false)
    {
        $row = parent::translateRow($row, $reversed);

        if (!isset($row['gap_id_organization'])) {
            throw new \Exception('Organization ID is needed for translations');
        }

        if (array_key_exists('location', $row)) {
            $location = $this->agenda->matchLocation($row['location'], $row['gap_id_organization']);
            $row['gap_id_location']     = $location['glo_id_location'];
        }

        if (array_key_exists('attended_by', $row)) {
            $row['gap_id_attended_by']  = $this->agenda->matchHealthcareStaff($row['attended_by'], $row['gap_id_organization']);
        }

        if (array_key_exists('activity', $row)) {
            $row['gap_id_activity']     = $this->agenda->matchActivity($row['activity'], $row['gap_id_organization']);
        }

        $row['gap_source']          = 'emma';
        $row['gap_code']            = 'A';

        return $row;
    }
}