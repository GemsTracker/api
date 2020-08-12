<?php


namespace Gems\Rest\Fhir\Model;


use Gems\Rest\Fhir\Model\Transformer\AppointmentParticipantTransformer;
use Gems\Rest\Fhir\Model\Transformer\AppointmentServiceTypeTransformer;
use Gems\Rest\Fhir\Model\Transformer\AppointmentStatusTransformer;
use Gems\Rest\Fhir\Model\Transformer\EpisodeOfCarePatientTransformer;
use Gems\Rest\Fhir\Model\Transformer\EpisodeOfCarePeriodTransformer;
use Gems\Rest\Fhir\Model\Transformer\EpisodeOfCareStatusTransformer;
use Gems\Rest\Fhir\Model\Transformer\IntTransformer;
use Gems\Rest\Fhir\Model\Transformer\ManagingOrganizationTransformer;
use Gems\Rest\Fhir\Model\Transformer\PatientReferenceTransformer;
use MUtil\Model\Type\JsonData;

class EpisodeOfCareModel extends \Gems_Model_JoinModel
{
    public function __construct()
    {
        parent::__construct('episodesofcare', 'gems__episodes_of_care', 'gec');

        $this->addColumn(new \Zend_Db_Expr('\'EpisodeOfCare\''), 'resourceType');

        $this->set('resourceType', 'label', 'resourceType');
        //$this->addColumn(new \Zend_Db_Expr('CONCAT(gr2o_patient_nr, "@", gr2o_id_organization)'), 'identifier');
        //$this->addColumn('grc_success', 'active');
        //$this->addColumn(new \Zend_Db_Expr("CASE grs_gender WHEN 'M' THEN 'male' WHEN 'F' THEN 'female' ELSE 'unknown' END"), 'gender');
        $this->addTable('gems__respondent2org', ['gec_id_user' => 'gr2o_id_user', 'gec_id_organization', 'gr2o_id_organization'], 'gr2o', false);
        $this->addTable('gems__organizations', ['gec_id_organization' => 'gor_id_organization'], 'gor', false);
        /*$this->addTable('gems__respondents', ['grs_id_user' =>  'gap_id_user'], 'grs');
        $this->addLeftTable('gems__agenda_activities', ['gap_id_activity' =>  'gaa_id_activity'], 'gaa');
        $this->addLeftTable('gems__agenda_staff', ['gap_id_attended_by' =>  'gas_id_staff'], 'gas');
        $this->addLeftTable('gems__locations', ['gap_id_location' =>  'glo_id_location'], 'glo');

        $this->addColumn('gap_admission_time', 'admission_date');*/

        $this->set('gec_episode_of_care_id', 'label', $this->_('id'), 'apiName', 'id');
        $this->set('gec_status', 'label', $this->_('status'), 'apiName', 'status');
        $this->set('patient', 'label', $this->_('patient'), 'apiName', 'patient');
        $this->set('period', 'label', $this->_('period'), 'apiName', 'period');
        $this->set('managingOrganization', 'label', $this->_('managingOrganization'), 'apiName', 'managingOrganization');

        $jsonType = new JsonData(10);
        $jsonType->apply($this, 'gec_diagnosis_data', false);
        $jsonType->apply($this, 'gec_extra_data',     false);

        $this->addTransformer(new EpisodeOfCareStatusTransformer());
        $this->addTransformer(new EpisodeOfCarePeriodTransformer());
        $this->addTransformer(new PatientReferenceTransformer('patient'));
        $this->addTransformer(new ManagingOrganizationTransformer('gec_id_organization', true));
        $this->addTransformer(new IntTransformer(['gec_episode_of_care_id']));

        /*$this->addTransformer(new PatientHumanNameTransformer());
        $this->addTransformer(new PatientTelecomTransformer());
        $this->addTransformer(new BooleanTransformer(['grc_success']));*/

    }
}
