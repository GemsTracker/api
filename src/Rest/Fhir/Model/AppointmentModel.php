<?php


namespace Gems\Rest\Fhir\Model;


use Gems\Rest\Fhir\Model\Transformer\AppointmentParticipantTransformer;
use Gems\Rest\Fhir\Model\Transformer\AppointmentServiceTypeTransformer;
use Gems\Rest\Fhir\Model\Transformer\AppointmentStatusTransformer;
use Gems\Rest\Fhir\Model\Transformer\IntTransformer;

class AppointmentModel extends \Gems_Model_AppointmentModel
{
    public function __construct()
    {
        parent::__construct();

        //$this->addColumn(new \Zend_Db_Expr('CONCAT(gr2o_patient_nr, "@", gr2o_id_organization)'), 'identifier');
        //$this->addColumn('grc_success', 'active');
        //$this->addColumn(new \Zend_Db_Expr("CASE grs_gender WHEN 'M' THEN 'male' WHEN 'F' THEN 'female' ELSE 'unknown' END"), 'gender');

        $this->addTable('gems__respondents', ['grs_id_user' =>  'gap_id_user'], 'grs');
        $this->addLeftTable('gems__agenda_activities', ['gap_id_activity' =>  'gaa_id_activity'], 'gaa');
        $this->addLeftTable('gems__agenda_staff', ['gap_id_attended_by' =>  'gas_id_staff'], 'gas');
        $this->addLeftTable('gems__locations', ['gap_id_location' =>  'glo_id_location'], 'glo');

        $this->addColumn('gap_admission_time', 'admission_date');

        $this->set('gap_id_appointment', 'label', $this->_('id'), 'apiName', 'id');
        $this->set('gap_status', 'label', $this->_('active'), 'apiName', 'status');
        $this->set('gap_admission_time', 'label', $this->_('start'), 'apiName', 'start');
        // Search options
        $this->set('admission_date', 'label', $this->_('date'), 'apiName', 'date');

        $this->set('gap_discharge_time', 'label', $this->_('end'), 'apiName', 'end');
        $this->set('gap_created', 'label', $this->_('created'), 'apiName', 'created');
        $this->set('gap_subject', 'label', $this->_('comment'), 'apiName', 'comment');
        $this->set('gap_comment', 'label', $this->_('description'), 'apiName', 'description');

        $this->set('serviceType', 'label', $this->_('serviceType'));

        $this->set('gap_created', 'label', $this->_('created'), 'apiName', 'created');
        $this->set('gap_changed', 'label', $this->_('changed'), 'apiName', 'changed');

        // Search options
        $this->set('service-type', 'label', $this->_('service-type'));
        $this->set('service-type.display', 'label', $this->_('service-type.display'));

        $this->set('participant', 'label', $this->_('participant'));
        // Search options
        $this->set('patient', 'label', $this->_('patient'));
        $this->set('patient.email', 'label', $this->_('patient.email'));
        $this->set('practitioner', 'label', $this->_('practitioner'));
        $this->set('practitioner.name', 'label', $this->_('practitioner.name'));
        $this->set('location', 'label', $this->_('location'));
        $this->set('location.name', 'label', $this->_('location.name'));


        $this->addTransformer(new AppointmentStatusTransformer());
        $this->addTransformer(new AppointmentServiceTypeTransformer());
        $this->addTransformer(new AppointmentParticipantTransformer());
        $this->addTransformer(new IntTransformer(['gap_id_appointment']));

        /*$this->addTransformer(new PatientHumanNameTransformer());
        $this->addTransformer(new PatientTelecomTransformer());
        $this->addTransformer(new BooleanTransformer(['grc_success']));*/

    }
}
