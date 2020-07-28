<?php


namespace Gems\Rest\Fhir\Model;


use Gems\Rest\Fhir\Model\Transformer\PatientReferenceTransformer;
use Gems\Rest\Fhir\Model\Transformer\TreatmentIdTransformer;
use Gems\Rest\Fhir\Model\Transformer\TreatmentStatusTransformer;
use MUtil\Translate\TranslateableTrait;

class TreatmentModel extends \MUtil_Model_SelectModel
{
    const NAME = 'treatment';

    use TranslateableTrait;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    public function __construct()
    {
    }

    public function afterRegistry()
    {
        $select = $this->getTreatmentSelect();
        parent::__construct($select, self::NAME);

        $this->set('resourceType', 'label', 'resourceType');

        $this->set('id', 'label', $this->_('id'), 'apiName', 'id');
        $this->set('treatment_name', 'label', $this->_('title'), 'apiName', 'title');
        $this->set('treatment_start_datetime', 'label', $this->_('created'), 'apiName', 'created');
        $this->set('subject', 'label', $this->_('subject'));
        $this->set('status', 'label', $this->_('status'));

        // Search options
        $this->set('patient', 'label', $this->_('patient'));
        $this->set('patient.email', 'label', $this->_('patient.email'));

        $this->addTransformer(new PatientReferenceTransformer('subject'));
        $this->addTransformer(new TreatmentIdTransformer());
        $this->addTransformer(new TreatmentStatusTransformer());

    }

    protected function getTreatmentSelect()
    {
        $select = $this->db->select();

        $select->from(['gr2o' => 'gems__respondent2org'], [])
            ->join(['grs' => 'gems__respondents'], 'gr2o_id_user = grs_id_user', [])
            ->joinLeft(
                ['r2t' => 'gems__respondent2track'],
                'gr2t_id_user = gr2o_id_user AND gr2t_id_organization = gr2o_id_organization',
                []
            )
            ->joinLeft(
                ['rc' => 'gems__reception_codes'],
                'gr2t_reception_code = grc_id_reception_code',
                []
            )
            ->joinLeft(
                ['gtap' => 'gems__track_appointments'],
                'gtap_id_track = gr2t_id_track AND gtap_field_code = \'treatmentAppointment\'',
                []
            )
            ->joinLeft(
                ['gr2t2a' => 'gems__respondent2track2appointment'],
                'gr2t2a_id_app_field = gtap_id_app_field',
                []
            )
            ->joinLeft(
                ['ap1' => 'gems__appointments'],
                'gr2t2a_id_appointment = ap1.gap_id_appointment',
                []
            )
            ->joinLeft(
                ['r2t2t' => 'pulse__respondent2track2treatment'],
                'pr2t2t_id_respondent_track = gr2t_id_respondent_track',
                []
            )
            ->joinLeft(
                ['pt1' => 'pulse__treatments'],
                'pr2t2t_id_treatment = pt1.ptr_id_treatment AND pt1.ptr_name != \'- algemeen -\'',
                []
            )
            ->joinLeft(
                ['ap2' => 'gems__appointments'],
                'ap2.gap_id_user = gr2o_id_user AND ap2.gap_id_organization = gr2o_id_organization',
                []
            )
            ->joinLeft(
                ['aa' => 'gems__agenda_activities'],
                'ap2.gap_id_activity = gaa_id_activity',
                []
            )
            ->joinLeft(
                ['a2t' => 'pulse__activity2treatment'],
                'pa2t_activity = gaa_name AND pa2t_active = 1',
                []
            )
            ->joinLeft(
                ['pt2' => 'pulse__treatments'],
                'pa2t_id_treatment = pt2.ptr_id_treatment AND pt2.ptr_name != \'- algemeen -\'',
                []
            )
            ->columns(
                [
                    'resourceType' => new \Zend_Db_Expr('\'Treatment\''),
                    'id' => new \Zend_Db_Expr('
                    CASE 
                        WHEN pt2.ptr_id_treatment IS NOT NULL THEN CONCAT(\'A\', ap2.gap_id_appointment)
                        WHEN pt1.ptr_id_treatment IS NOT NULL THEN CONCAT(\'RT\', gr2t_id_respondent_track)
                    END'),

                    'gr2o.gr2o_patient_nr',
                    'gr2o.gr2o_id_organization',
                    'grs.grs_first_name',
                    'grs.grs_initials_name',
                    'grs.grs_surname_prefix',
                    'grs.grs_last_name',

                    'treatment_name' => new \Zend_Db_Expr('
                    CASE 
                        WHEN pt2.ptr_id_treatment IS NOT NULL THEN pt2.ptr_name
                        WHEN pt1.ptr_id_treatment IS NOT NULL THEN pt1.ptr_name
                    END'),
                    'treatment_start_datetime' => new \Zend_Db_Expr('
                    CASE 
                        WHEN pt2.ptr_id_treatment IS NOT NULL THEN ap2.gap_admission_time
                        WHEN pt1.ptr_id_treatment IS NOT NULL THEN 
                            CASE
                                WHEN ap1.gap_id_appointment THEN ap1.gap_admission_time
                                ELSE DATE_ADD(gr2t_start_date, INTERVAL 14 DAY)
                            END
                    END'),

                    'treatment_start_date' => new \Zend_Db_Expr('
                    DATE(CASE 
                        WHEN pt2.ptr_id_treatment IS NOT NULL THEN ap2.gap_admission_time
                        WHEN pt1.ptr_id_treatment IS NOT NULL THEN 
                            CASE
                                WHEN ap1.gap_id_appointment THEN ap1.gap_admission_time
                                ELSE DATE_ADD(gr2t_start_date, INTERVAL 14 DAY)
                            END
                    END)'),
                    'status' => new \Zend_Db_Expr('
                    CASE
                        WHEN pt2.ptr_id_treatment THEN
                            CASE
                                WHEN ap2.gap_status = \'AC\' THEN \'active\'
                                WHEN ap2.gap_status = \'CO\' THEN \'completed\'
                                WHEN ap2.gap_status = \'CA\' THEN \'revoked\'
                                WHEN ap2.gap_status = \'AB\' THEN \'revoked\'
                                ELSE \'unknown\'
                            END
                        WHEN pt1.ptr_id_treatment THEN
                            CASE
                                WHEN gr2t_completed >= gr2t_count THEN \'completed\'
                                WHEN gr2t_end_date >= NOW() THEN \'completed\'
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
                            END
                           ELSE \'unknown\'
                    END'),
                    'treatment_code' => new \Zend_Db_Expr('
                    CASE 
                        WHEN pt2.ptr_id_treatment IS NOT NULL THEN pt2.ptr_code
                        WHEN pt1.ptr_id_treatment IS NOT NULL THEN pt1.ptr_code
                    END'),

                ]
            )
            ->where('pt1.ptr_id_treatment IS NOT NULL OR pt2.ptr_id_treatment IS NOT NULL')
            ->order(
                [
                    new \Zend_Db_Expr('treatment_code IS NULL DESC'),
                    'treatment_code DESC',
                    'treatment_start_datetime',
                ]
            );

        return $select;
    }
}
