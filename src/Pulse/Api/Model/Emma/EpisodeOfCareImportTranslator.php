<?php


namespace Pulse\Api\Model\Emma;


use Psr\Log\LoggerInterface;
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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var OrganizationRepository
     */
    protected $organizationRepository;

    public function __construct(Adapter $db, AgendaDiagnosisRepository $agendaDiagnosisRepository, LoggerInterface $logger, OrganizationRepository $organizationRepository, \Gems_Agenda $agenda)
    {
        $this->agenda = $agenda;
        $this->agendaDiagnosisRepository = $agendaDiagnosisRepository;
        $this->db = $db;
        $this->logger = $logger;
        $this->organizationRepository = $organizationRepository;
    }

    public function translateEpisodes($rawEpisodes, $usersPerOrganization)
    {
        $episodesOfCare = [];
        foreach($rawEpisodes as $episode) {

            if (!isset($episode['episode_id'])) {
                // Skipping episode because no ID is set!
                $this->logger->warning(sprintf('Skipping import of episode because no episode id is set in episode.'), $episode);
                continue;
            }
            $id = $episode['episode_id'];
            $startDate = $this->translateDate($episode['start_date']);
            if (!isset($episodesOfCare[$id])) {
                if (!array_key_exists('organization', $episode)) {
                    // Skipping episode because organization is not set in episode!
                    $this->logger->warning(sprintf('Skipping import of episode because no organization is set in episode.'), $episode);
                    continue;
                }
                $organizationId = $this->organizationRepository->getOrganizationId($episode['organization']);
                if ($organizationId === null) {
                    // Skipping episode because organization ID could not be found!
                    $this->logger->warning(sprintf('Skipping import of episode because episode organization is unknown in pulse.'), $episode);
                    continue;
                }

                if (!isset($usersPerOrganization[$organizationId])) {
                    // Skipping episode because user does not exist in episode organization
                    $this->logger->warning(sprintf('Skipping import of episode because user does not exist in episode organization in pulse.'), $episode);
                    continue;
                }

                $episodesOfCare[$id] = [
                    'gec_id_in_source'      => $id,
                    'gec_id_user'           => $usersPerOrganization[$organizationId],
                    'gec_id_organization'   => $organizationId,

                    'gec_source'            => 'emma',
                    'gec_status'            => 'A',
                    'gec_startdate'         => $startDate,
                ];

                if (array_key_exists('info', $episode)) {
                    $episodesOfCare[$id]['gec_extra_data'] = $episode['info'];
                }
                if (array_key_exists('main_practitioner', $episode)) {
                    $mainPractitionerName = trim($episode['main_practitioner']);
                    $episodesOfCare[$id]['gec_id_attended_by'] = $this->agenda->matchHealthcareStaff($mainPractitionerName, $organizationId);
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

            $this->agendaDiagnosisRepository->matchDiagnosis(trim($episode['diagnosis_code']), 'emma', trim($episode['diagnosis_description']));

            $diagnosticData = [];
            if (array_key_exists('start_date', $episode)) {
                $diagnosticData['start_date'] = $episode['start_date'];
            }
            if (array_key_exists('end_date', $episode)) {
                $diagnosticData['end_date'] = $episode['end_date'];
            }
            if (array_key_exists('diagnosis_code', $episode)) {
                $diagnosticData['diagnosis_code'] = trim($episode['diagnosis_code']);
            }
            if (array_key_exists('diagnosis_description', $episode)) {
                $diagnosticData['diagnosis_description'] = trim($episode['diagnosis_description']);
            }

            $episodesOfCare[$id]['gec_diagnosis_data'][$episode['dbc_id']] = $diagnosticData;
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

        //if ($result->count() > 0) {
        if ($result->valid() && $result->count() > 0) {
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