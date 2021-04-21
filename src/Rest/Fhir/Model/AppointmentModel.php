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
        $this->addTable('gems__organizations', ['gap_id_organization' => 'gor_id_organization'],'gor');
        $this->addLeftTable('gems__agenda_activities', ['gap_id_activity' =>  'gaa_id_activity'], 'gaa');
        $this->addLeftTable('gems__agenda_staff', ['gap_id_attended_by' =>  'gas_id_staff'], 'gas');
        $this->addLeftTable('gems__locations', ['gap_id_location' =>  'glo_id_location'], 'glo');

        $this->addColumn('gap_admission_time', 'admission_date');
        $this->addColumn(new \Zend_Db_Expr('\'Appointment\''), 'resourceType');

        $this->set('resourceType', 'label', 'resourceType');

        $this->set('gap_id_appointment', 'label', 'id', 'apiName', 'id');
        $this->set('gap_status', 'label', 'active', 'apiName', 'status');
        $this->set('gap_admission_time', 'label', 'start', 'apiName', 'start');
        // Search options
        $this->set('admission_date', 'label', 'date', 'apiName', 'date');

        $this->set('gap_discharge_time', 'label', 'end', 'apiName', 'end');
        $this->set('gap_created', 'label', 'created', 'apiName', 'created');
        $this->set('gap_subject', 'label', 'comment', 'apiName', 'comment');
        $this->set('gap_comment', 'label', 'description', 'apiName', 'description');

        $this->set('serviceType', 'label', 'serviceType');

        $this->set('gap_created', 'label', 'created', 'apiName', 'created');
        $this->set('gap_changed', 'label', 'changed', 'apiName', 'changed');

        // Search options
        $this->set('service-type', 'label', 'service-type');
        $this->set('service-type.display', 'label', 'service-type.display');

        $this->set('participant', 'label', 'participant');
        // Search options
        $this->set('patient', 'label', 'patient');
        $this->set('patient.email', 'label', 'patient.email');
        $this->set('practitioner', 'label', 'practitioner');
        $this->set('practitioner.name', 'label', 'practitioner.name');
        $this->set('location', 'label', 'location');
        $this->set('location.name', 'label', 'location.name');


        $this->addTransformer(new AppointmentStatusTransformer());
        $this->addTransformer(new AppointmentServiceTypeTransformer());
        $this->addTransformer(new AppointmentParticipantTransformer());
        $this->addTransformer(new IntTransformer(['gap_id_appointment']));

        /*$this->addTransformer(new PatientHumanNameTransformer());
        $this->addTransformer(new PatientTelecomTransformer());
        $this->addTransformer(new BooleanTransformer(['grc_success']));*/

    }
}
