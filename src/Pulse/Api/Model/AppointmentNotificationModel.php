<?php

namespace Pulse\Api\Model;

use Gems\Rest\Fhir\Model\Transformer\IntTransformer;
use Pulse\Api\Model\Transformer\DigitalClinicAccountTransformer;
use Pulse\Api\Model\Transformer\RangeTransformer;

class AppointmentNotificationModel extends \Gems_Model_JoinModel
{
    public function __construct()
    {
        parent::__construct('appointmentNotification', 'gems__appointments', 'gap', false);
        $this->addTable('gems__respondent2org', ['gr2o_id_user' => 'gap_id_user', 'gr2o_id_organization' => 'gap_id_organization']);
        $this->addTable('pulse__respondent2account', ['pr2a_id_user' => 'gap_id_user', 'pr2a_id_organization' => 'gap_id_organization']);
        $this->addTable('pulse__account_groups', ['pr2a_id_group' => 'pag_id_group']);

        $this->addColumn(new \Zend_Db_Expr("CONCAT(gr2o_patient_nr, '@', gr2o_id_organization)"), 'patientNr');
        $this->addTransformer(new RangeTransformer('gap_admission_time'));
        $this->addTransformer(new DigitalClinicAccountTransformer());
        $this->addTransformer(new IntTransformer(['gap_id_appointment']));
    }

    public function afterRegistry()
    {
        $this->set('patientNr', [
            'label' => $this->_('Patient nr'),
        ]);

        $this->set('gap_id_appointment', [
            'label' => $this->_('Appointment ID'),
            'apiName' => 'appointmentId',
        ]);

        $this->set('gap_admission_time', [
            'label' => $this->_('Appointment Time'),
            'apiName' => 'appointmentTime',
        ]);

        $this->set('start', [
            'filterValue' => true,
        ]);
        $this->set('end', [
            'filterValue' => true,
        ]);
    }
}