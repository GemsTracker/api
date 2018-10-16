<?php


namespace Pulse\Api\Model\Emma;


use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class EpisodeOfCareImportTranslator
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
     * @var OrganizationRepository
     */
    protected $organizationRepository;

    public function __construct(Adapter $db, AgendaDiagnosisRepository $agendaDiagnosisRepository, OrganizationRepository $organizationRepository, \Gems_Agenda $agenda)
    {
        $this->agenda = $agenda;
        $this->agendaDiagnosisRepository = $agendaDiagnosisRepository;
        $this->db = $db;
        $this->organizationRepository = $organizationRepository;
    }

    public function translateEpisodes($rawEpisodes, $userId)
    {
        $episodesOfCare = [];
        foreach($rawEpisodes as $episode) {
            $id = $episode['episode_id'];
            $startDate = $this->translateDate($episode['start_date']);
            if (!isset($episodesOfCare[$id])) {
                if (!array_key_exists('organization', $episode)) {
                    // Skipping appointment because organization is not set in appointment!
                    continue;
                }
                $organizationId = $this->organizationRepository->getOrganizationId($episode['organization']);
                if ($organizationId === null) {
                    // Skipping appointment because organization ID could not be found!
                    continue;
                }

                $episodesOfCare[$id] = [
                    'gec_id_in_source'      => $id,
                    'gec_id_user'           => $userId,
                    'gec_id_organization'   => $organizationId,

                    'gec_source'            => 'emma',
                    'gec_status'            => 'A',
                    'gec_startdate'         => $startDate,
                ];

                if (array_key_exists('info', $episode)) {
                    $episodesOfCare[$id]['gec_extra_data'] = $episode['info'];
                }
                if (array_key_exists('main_practitioner', $episode)) {
                    $episodesOfCare[$id]['gec_id_attended_by'] = $this->agenda->matchHealthcareStaff($episode['main_practitioner'], $organizationId);
                }
            } elseif ($startDate->isEarlier($episodesOfCare[$id]['gec_startdate'])) {
                $episodesOfCare[$id]['gec_startdate'] = $startDate;
            }

            if (isset($episode['info']) && !empty($episode['info'])) {
                if (!isset($episodesOfCare[$id]['gec_extra_data']) || $episodesOfCare[$id]['gec_extra_data'] === null) {
                    $episodesOfCare[$id]['gec_extra_data'] = $episode['info'];
                } else {
                    foreach($episodesOfCare[$id]['gec_extra_data'] as $formName=>$formDataCollection) {
                        if (isset($episode['info'][$formName])) {
                            $existingCodes = array_flip(array_column($formDataCollection, 'code'));
                            foreach($episode['info'][$formName] as $newFormdata) {
                                if (!isset($existingCodes[$newFormdata['code']])) {
                                    $episodesOfCare[$id]['gec_extra_data'][$formName][] = $newFormdata;
                                }
                            }
                        }
                    }
                }
            }

            $this->agendaDiagnosisRepository->matchDiagnosis($episode['diagnosis_code'], 'emma', $episode['diagnosis_description']);

            $episodesOfCare[$id]['gec_diagnosis_data'][$episode['dbc_id']] = [
                'start_date' => $episode['start_date'],
                'end_date' => $episode['end_date'],
                'diagnosis_code' => $episode['diagnosis_code'],
                'diagnosis_description' => $episode['diagnosis_description'],
            ];
        }

        foreach($episodesOfCare as $key=>$episode) {
            $episodesOfCare[$key]['gec_extra_data'] = json_encode($episode['gec_extra_data']);
            $episodesOfCare[$key]['gec_diagnosis'] = $this->getDiagnosisFromDiagnosisData($episode['gec_diagnosis_data']);
            $episodesOfCare[$key]['gec_diagnosis_data'] = json_encode($episode['gec_diagnosis_data']);

            if ($episodeId = $this->getEpisodeId($episode['gec_id_in_source'], $episode['gec_id_organization'], $episode['gec_source'])) {
                $episodesOfCare[$key]['gec_episode_of_care_id'] = $episodeId;
            }
        }

        return $episodesOfCare;
    }

    protected function getDiagnosisFromDiagnosisData($diagnosisData)
    {
        return join(',', array_column($diagnosisData, 'diagnosis_description'));
    }

    protected function getEpisodeId($sourceId, $organizationId, $source)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__episodes_of_care')
            ->columns(['gec_episode_of_care_id'])
            ->where(['gec_id_in_source' => $sourceId, 'gec_id_organization' => $organizationId, 'gec_source' => $source]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->count() > 0) {
            $episode = $result->current();
            return $episode['gec_episode_of_care_id'];
        }
        return false;
    }

    protected function translateDate($value)
    {
        if (strpos($value, '+') === 19 || strpos($value, '.') === 19) {
            $value = substr($value, 0, 19);
        }
        $date = new \MUtil_Date($value, \MUtil_Date::ISO_8601);
        return $date;
    }
}