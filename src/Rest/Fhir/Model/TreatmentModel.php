<?php


namespace Gems\Rest\Fhir\Model;


use Gems\Rest\Fhir\Model\Transformer\PatientReferenceTransformer;
use Gems\Rest\Fhir\Model\Transformer\TreatmentIdTransformer;
use Gems\Rest\Fhir\Model\Transformer\TreatmentStatusTransformer;
use MUtil\Translate\TranslateableTrait;

class TreatmentModel extends \Gems_Model_JoinModel
{
    const NAME = 'treatment';

    const RESPONDENTTRACKMODEL = 'respondentTrackTreatments';

    use TranslateableTrait;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    public function __construct()
    {
        parent::__construct(self::RESPONDENTTRACKMODEL, 'gems__respondent2org', 'gr2o', false);

        $this->addTable('gems__respondents', ['gr2o_id_user' => 'grs_id_user'], 'grs', false);
        $this->addTable('gems__respondent2track', ['gr2t_id_user' => 'gr2o_id_user', 'gr2t_id_organization' => 'gr2o_id_organization'], 'gr2t', false);
        $this->addTable('gems__reception_codes', ['gr2t_reception_code' => 'grc_id_reception_code'], 'rc', false);
        $this->addTable('pulse__respondent2track2treatment', ['pr2t2t_id_respondent_track' => 'gr2t_id_respondent_track'], 'pr2t2t', false);
        $this->addTable('pulse__treatments', ['pr2t2t_id_treatment' => 'ptr_id_treatment', 'ptr_name != \'- algemeen -\''], 'ptr', false);
        $this->addLeftTable('gems__track_appointments', ['gtap_id_track' => 'gr2t_id_track', 'gtap_field_code' => new \Zend_Db_Expr('\'treatmentAppointment\'')], 'gtap', false);
        $this->addLeftTable('gems__respondent2track2appointment', ['gr2t2a_id_app_field' => 'gtap_id_app_field', 'gr2t2a_id_respondent_track' => 'gr2t_id_respondent_track'], 'gr2t2a', false);
        $this->addLeftTable('gems__appointments', ['gr2t2a_id_appointment' => 'gap_id_appointment'], 'gap', false);

        $this->addColumn(new \Zend_Db_Expr('\'Treatment\''), 'resourceType');
        $this->addColumn(new \Zend_Db_Expr('CONCAT(\'RT\',gr2t_id_respondent_track)'), 'id');
        $this->addColumn(new \Zend_Db_Expr('ptr_name'), 'treatment_name');
        $this->addColumn(new \Zend_Db_Expr('ptr_id_treatment'), 'treatment_id');
        $this->addColumn(new \Zend_Db_Expr('
CASE 
    WHEN gap_id_appointment THEN gap_admission_time 
    ELSE DATE_ADD(gr2t_start_date,INTERVAL 14 DAY) 
END'), 'treatment_start_datetime');
        $this->addColumn(new \Zend_Db_Expr('
DATE(CASE 
    WHEN gap_id_appointment THEN gap_admission_time 
    ELSE DATE_ADD(gr2t_start_date,INTERVAL 14 DAY) 
END)'), 'treatment_start_date');

        $this->addColumn(new \Zend_Db_Expr('
CASE 
    WHEN gr2t_completed >= gr2t_count THEN \'completed\' 
    WHEN gr2t_end_date <= NOW() THEN \'completed\' 
    WHEN gr2t_reception_code = \'OK\' THEN \'active\' 
    WHEN gr2t_reception_code = \'retract\' THEN \'revoked\' 
    WHEN gr2t_reception_code = \'stop\' THEN \'revoked\' 
    WHEN gr2t_reception_code = \'refused\' THEN \'revoked\' 
    WHEN gr2t_reception_code = \'misdiag\' THEN \'revoked\' 
    WHEN gr2t_reception_code = \'diagchange\' THEN \'revoked\' 
    WHEN gr2t_reception_code = \'agenda_cancelled\' THEN \'revoked\' 
    WHEN gr2t_reception_code = \'incap\' THEN \'revoked\' 
    WHEN gr2t_reception_code = \'mistake\' THEN \'entered-in-error\' 
    ELSE \'unknown\' 
END'), 'status');
        $this->addColumn(new \Zend_Db_Expr('ptr_code'),'treatment_code');

        $this->addTransformer(new PatientReferenceTransformer('subject'));
        $this->addTransformer(new TreatmentIdTransformer());
        $this->addTransformer(new TreatmentStatusTransformer(self::RESPONDENTTRACKMODEL));

        $this->set('treatment_start_datetime', [
                'type' => \MUtil_Model::TYPE_DATETIME,
                'storageFormat' => 'yyyy-MM-dd HH:mm:ss'
            ]
        );

        $this->setOnSave('treatment_start_datetime', [$this, 'formatSaveDate']);
        $this->setOnLoad('treatment_start_datetime', [$this, 'formatLoadDate']);
    }

    public function afterRegistry()
    {
        $this->set('resourceType', 'label', 'resourceType');
        $this->set('treatment_name', 'label', $this->_('title'), 'apiName', 'title');
        $this->set('treatment_id', 'label', $this->_('code'), 'apiName', 'code');
        $this->set('treatment_start_datetime', [
                'label' => $this->_('created'),
                'apiName' =>'created',
                'type' => \MUtil_Model::TYPE_DATETIME,
                'storageFormat' => 'yyyy-MM-dd HH:mm:ss'
            ]
        );

        $this->set('subject', 'label', $this->_('subject'));
        $this->set('status', 'label', $this->_('status'));

        // Search options
        $this->set('patient', 'label', $this->_('patient'));
        $this->set('patient.email', 'label', $this->_('patient.email'));
    }
}
