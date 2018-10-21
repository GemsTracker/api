<?php


namespace Pulse\Api\Model\Emma;


use Pulse\Api\Model\ApiModelTranslator;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class AppointmentImportTranslator extends ApiModelTranslator
{
    /**
     * @var \Gems_Agenda
     */
    protected $agenda;

    /**
     * @var Adapter
     */
    protected $db;

    /**
     * @var array Api translations for the respondent
     */
    public $translations = [
        'gap_id_in_source' => 'id',
        'gap_admission_time' => 'admission_time',
        'gap_status' => 'status',
        'gap_diagnosis_code' => 'diagnosis_code',
        //'gap_id_episode' => 'episode_id',
        //'gap_dbc_id' => 'dbc_id',
        // 'location',
        //'' => 'attended_by',
        //'' => 'activity',
        //'' => 'room',
    ];

    public function __construct(Adapter $db, \Gems_Agenda $agenda)
    {
        $this->agenda = $agenda;
        $this->db = $db;
        parent::__construct(null);
    }

    protected function findEpisodeOfCare($sourceId, $source, $organizationId)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__episodes_of_care')
            ->columns(['gec_episode_of_care_id'])
            ->where(
                [
                    'gec_id_in_source' => $sourceId,
                    'gec_source' => $source,
                    'gec_id_organization' => $organizationId,
                ]
            );
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        //if ($result->count() > 0) {
        if ($result->valid()) {
            $episode = $result->current();
            return $episode['gec_episode_of_care_id'];
        }
        return null;
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
        $source = 'emma';
        if (!isset($row['gap_id_organization'])) {
            throw new \Exception('Organization ID is needed for translations');
        }

        if (array_key_exists('location', $row)) {
            $locationName = trim($row['location']);
            $location = $this->agenda->matchLocation($locationName, $row['gap_id_organization']);
            $row['gap_id_location']     = $location['glo_id_location'];
        }

        if (array_key_exists('attended_by', $row)) {
            $healthCareStaffName = trim($row['attended_by']);
            $row['gap_id_attended_by']  = $this->agenda->matchHealthcareStaff($healthCareStaffName, $row['gap_id_organization']);
        }

        if (array_key_exists('activity', $row)) {
            $activityName = $row['activity'];
            $row['gap_id_activity']     = $this->agenda->matchActivity($activityName, $row['gap_id_organization']);
        }
        if (array_key_exists('episode_id', $row)) {
            $episodeId = (int) $row['episode_id'];
            $row['gap_id_episode']     = $this->findEpisodeOfCare($episodeId, $source, $row['gap_id_organization']);
        }

        $row['gap_source']          = $source;
        $row['gap_code']            = 'A';

        return $row;
    }
}