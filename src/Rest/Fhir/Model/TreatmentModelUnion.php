<?php


namespace Gems\Rest\Fhir\Model;


use Gems\Rest\Fhir\Model\Transformer\PatientReferenceTransformer;
use Gems\Rest\Fhir\Model\Transformer\TreatmentIdTransformer;
use Gems\Rest\Fhir\Model\Transformer\TreatmentStatusTransformer;
use MUtil\Translate\TranslateableTrait;

class TreatmentModel extends \MUtil_Model_UnionModel
{
    const NAME = 'treatment';

    const APPOINTMENTMODEL = 'appointmentTreatments';
    const RESPONDENTTRACKMODEL = 'respondentTrackTreatments';

    use TranslateableTrait;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    public function __construct()
    {
        parent::__construct(self::NAME);

        $appointmentTreatmentModel = $this->getAppointmentTreatmentModel();
        $respondentTrackTreatmentModel = $this->getRespondentTrackTreatmentModel();

        $this->addUnionModel($respondentTrackTreatmentModel);
        $this->addUnionModel($appointmentTreatmentModel);
    }

    public function afterRegistry()
    {
        $this->set('resourceType', 'label', 'resourceType');
        $this->set('treatment_name', 'label', 'title', 'apiName', 'title');
        $this->set('treatment_start_datetime', [
                'label' => 'created',
                'apiName' =>'created',
                'type' => \MUtil_Model::TYPE_DATETIME,
                'storageFormat' => 'yyyy-MM-dd HH:mm:ss'
            ]
        );

        $this->set('subject', 'label', 'subject');
        $this->set('status', 'label', 'status');

        // Search options
        $this->set('patient', 'label', 'patient');
        $this->set('patient.email', 'label', 'patient.email');
    }

    protected function getAppointmentTreatmentModel()
    {
        $model = new \Gems_Model_JoinModel(self::APPOINTMENTMODEL, 'gems__respondent2org', 'gr2o', false);
        $model->addTable('gems__respondents', ['gr2o_id_user' => 'grs_id_user'], 'grs', false);
        $model->addTable('gems__appointments', ['gap_id_user' => 'gr2o_id_user', 'gap_id_organization' => 'gr2o_id_organization'], 'gap', false);
        $model->addTable('gems__agenda_activities', ['gap_id_activity' => 'gaa_id_activity'], 'gaa', false);
        $model->addTable('pulse__activity2treatment', ['pa2t_activity' => 'gaa_name'], 'pa2t', false);
        $model->addTable('pulse__treatments', ['pa2t_id_treatment' => 'ptr_id_treatment', 'ptr_name != \'- algemeen -\''], 'ptr', false);


        $model->addColumn(new \Zend_Db_Expr('\'Treatment\''), 'resourceType');
        $model->addColumn(new \Zend_Db_Expr('CONCAT(\'A\',gap_id_appointment)'), 'id');
        $model->addColumn(new \Zend_Db_Expr('ptr_name'), 'treatment_name');
        $model->addColumn(new \Zend_Db_Expr('gap_admission_time'), 'treatment_start_datetime');
        $model->addColumn(new \Zend_Db_Expr('DATE(gap_admission_time)'), 'treatment_start_date');

        $model->addColumn(new \Zend_Db_Expr('
CASE 
    WHEN gap_status = \'AC\' THEN \'active\' 
    WHEN gap_status = \'CO\' THEN \'completed\' 
    WHEN gap_status = \'CA\' THEN \'revoked\'
        
    WHEN gap_status = \'AB\' THEN \'revoked\' 
    ELSE \'unknown\' 
END'), 'status');
        $model->addColumn(new \Zend_Db_Expr('ptr_code'), 'treatment_code');

        $model->addTransformer(new PatientReferenceTransformer('subject'));
        $model->addTransformer(new TreatmentIdTransformer());
        $model->addTransformer(new TreatmentStatusTransformer(self::APPOINTMENTMODEL));

        $model->set('treatment_start_datetime', [
                'type' => \MUtil_Model::TYPE_DATETIME,
                'storageFormat' => 'yyyy-MM-dd HH:mm:ss'
            ]
        );

        $model->setOnSave('treatment_start_datetime', [$model, 'formatSaveDate']);
        $model->setOnLoad('treatment_start_datetime', [$model, 'formatLoadDate']);

        return $model;
    }

    /**
     * Calculates the total number of items in a model result with certain filters
     *
     * @param array $filter Filter array, num keys contain fixed expresions, text keys are equal or one of filters
     * @param array $sort Sort array field name => sort type
     * @return integer number of total items in model result
     * @throws \Zend_Db_Select_Exception
     */
    public function getItemCount($filter = true, $sort = true)
    {
        $count = 0;
        foreach ($this->_getFilterModels($filter) as $name => $model) {
            if (method_exists($model, 'getItemCount')) {
                $count += $model->getItemCount($filter);
            }
        }

        return $count;
    }

    protected function getRespondentTrackTreatmentModel()
    {
        $model = new \Gems_Model_JoinModel(self::RESPONDENTTRACKMODEL, 'gems__respondent2org', 'gr2o', false);
        $model->addTable('gems__respondents', ['gr2o_id_user' => 'grs_id_user'], 'grs', false);
        $model->addTable('gems__respondent2track', ['gr2t_id_user' => 'gr2o_id_user', 'gr2t_id_organization' => 'gr2o_id_organization'], 'gr2t', false);
        $model->addTable('gems__reception_codes', ['gr2t_reception_code' => 'grc_id_reception_code'], 'rc', false);
        $model->addTable('pulse__respondent2track2treatment', ['pr2t2t_id_respondent_track' => 'gr2t_id_respondent_track'], 'pr2t2t', false);
        $model->addTable('pulse__treatments', ['pr2t2t_id_treatment' => 'ptr_id_treatment', 'ptr_name != \'- algemeen -\''], 'ptr', false);
        $model->addLeftTable('gems__track_appointments', ['gtap_id_track' => 'gr2t_id_track', 'gtap_field_code' => new \Zend_Db_Expr('\'treatmentAppointment\'')], 'gtap', false);
        $model->addLeftTable('gems__respondent2track2appointment', ['gr2t2a_id_app_field' => 'gtap_id_app_field', 'gr2t2a_id_respondent_track' => 'gr2t_id_respondent_track'], 'gr2t2a', false);
        $model->addLeftTable('gems__appointments', ['gr2t2a_id_appointment' => 'gap_id_appointment'], 'gap', false);

        $model->addColumn(new \Zend_Db_Expr('\'Treatment\''), 'resourceType');
        $model->addColumn(new \Zend_Db_Expr('CONCAT(\'RT\',gr2t_id_respondent_track)'), 'id');
        $model->addColumn(new \Zend_Db_Expr('ptr_name'), 'treatment_name');
        $model->addColumn(new \Zend_Db_Expr('
CASE 
    WHEN gap_id_appointment THEN gap_admission_time 
    ELSE DATE_ADD(gr2t_start_date,INTERVAL 14 DAY) 
END'), 'treatment_start_datetime');
        $model->addColumn(new \Zend_Db_Expr('
DATE(CASE 
    WHEN gap_id_appointment THEN gap_admission_time 
    ELSE DATE_ADD(gr2t_start_date,INTERVAL 14 DAY) 
END)'), 'treatment_start_date');

        $model->addColumn(new \Zend_Db_Expr('
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
        $model->addColumn(new \Zend_Db_Expr('ptr_code'),'treatment_code');

        $model->addTransformer(new PatientReferenceTransformer('subject'));
        $model->addTransformer(new TreatmentIdTransformer());
        $model->addTransformer(new TreatmentStatusTransformer(self::RESPONDENTTRACKMODEL));

        $model->set('treatment_start_datetime', [
                'type' => \MUtil_Model::TYPE_DATETIME,
                'storageFormat' => 'yyyy-MM-dd HH:mm:ss'
            ]
        );

        $model->setOnSave('treatment_start_datetime', [$model, 'formatSaveDate']);
        $model->setOnLoad('treatment_start_datetime', [$model, 'formatLoadDate']);

        return $model;
    }
}
