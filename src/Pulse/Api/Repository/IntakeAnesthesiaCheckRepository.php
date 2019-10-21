<?php


namespace Pulse\Api\Repository;


class IntakeAnesthesiaCheckRepository
{
    public function __construct()
    {
    }

    protected function getModel()
    {
        $model = new \Gems_Model_JoinModel('anaesthesia', 'gems__appointments');
        $model->setKeys([\Gems_Model::APPOINTMENT_ID => 'gap_id_appointment']);
        $model->addTable('gems__respondent2org',
            ['gap_id_user' => 'gr2o_id_user', 'gap_id_organization' => 'gr2o_id_organization']);
        $model->addTable('pulse__anaesthesia_tokens', ['gap_id_appointment' => 'pat_id_appointment']);
        $model->addTable('gems__tokens', ['pat_id_token' => 'gto_id_token']);

        return $model;
    }

    public function getCurrentAneasthesiaToken($patientNr, $organizationId, $respondentTrackId=null)
    {
        return $this->getCurrentToken(false, $patientNr, $organizationId, $respondentTrackId);
    }

    public function getCurrentIntakeToken($patientNr, $organizationId, $respondentTrackId=null)
    {
        return $this->getCurrentToken(true, $patientNr, $organizationId, $respondentTrackId);
    }

    protected function getCurrentToken($isIntake, $patientNr, $organizationId, $respondentTrackId=null)
    {
        $model = $this->getModel();
        $filter = [
            'gr2o_patient_nr' => $patientNr,
            'gr2o_id_organization' => $organizationId,
            'gap_status' => 'AC',
            'gto_reception_code' => ['OK', 'checked', 'rejected'],
        ];

        if ($isIntake) {
            $filter['pat_intake'] = 1;
        } else {
            $filter['pat_aneasthesia'] = 1;
        }

        if ($respondentTrackId !== null) {

            $model->addTable('gems__respondent2track', ['gto_id_respondent_track' => 'gr2t_id_respondent_track']);
            $filter['gr2t_id_respondent_track'] = $respondentTrackId;
        }

        $sort = ['gap_admission_time DESC'];

        $tokenAppointment = $model->loadFirst($filter, $sort);

        if ($tokenAppointment) {

            $filterFields = [
                'gto_id_token',
                'gto_completion_time',
                'gto_reception_code',
                'gap_id_appointment',

            ];

            return array_intersect_key($tokenAppointment, array_flip($filterFields));
        }
        return null;
    }
}