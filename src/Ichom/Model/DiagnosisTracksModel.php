<?php

namespace Ichom\Model;


use Gems\Rest\Fhir\Model\Transformer\AllNumberTransformer;
use Gems\Rest\Fhir\Model\Transformer\BooleanTransformer;
use Gems\Rest\Fhir\Model\Transformer\PatientReferenceTransformer;
use Ichom\Model\Transformer\FlatRespondentTrackFieldTransformer;
use Ichom\Model\Transformer\PatientNameTransformer;

class DiagnosisTracksModel extends \Gems_Model_JoinModel
{
    public function __construct()
    {
        parent::__construct('RespondentDossierTemplates', 'gems__respondent2track', 'gr2t', false);
        $this->addTable('gems__respondent2org', ['gr2o_id_user' => 'gr2t_id_user', 'gr2o_id_organization' => 'gr2t_id_organization'], 'gr2o', false)
            ->addTable('gems__respondents', ['gr2t_id_user' => 'grs_id_user'], 'grs', false)
            ->addTable('gems__reception_codes', ['gr2t_reception_code' => 'grc_id_reception_code'], 'grc', false)
            ->addTable('gems__tracks', ['gr2t_id_track' => 'gtr_id_track'], 'gtr', false)
            ->addTable(['diagnosisField' => 'gems__track_fields'], ['diagnosisField.gtf_id_track' => 'gr2t_id_track', 'diagnosisField.gtf_field_type' => new \Zend_Db_Expr('\'diagnosis\'')], 'gtf', false)
            ->addTable(['diagnosisFieldValue' => 'gems__respondent2track2field'], ['gr2t_id_respondent_track' => 'diagnosisFieldValue.gr2t2f_id_respondent_track', 'diagnosisField.gtf_id_field' => 'diagnosisFieldValue.gr2t2f_id_field'], 'gr2t2f', false)
            ->addTable('gems__diagnosis2track', ['gdt_id_diagnosis' => 'diagnosisFieldValue.gr2t2f_value'], 'gdt', false)
            ->addTable(['treatmentField' => 'gems__track_fields'], ['treatmentField.gtf_id_track' => 'gr2t_id_track', 'treatmentField.gtf_field_type' => new \Zend_Db_Expr('\'treatmentDiagnosis\'')], 'gtf', false)
            ->addTable(['treatmentFieldValue' => 'gems__respondent2track2field'], ['gr2t_id_respondent_track' => 'treatmentFieldValue.gr2t2f_id_respondent_track', 'treatmentField.gtf_id_field' => 'treatmentFieldValue.gr2t2f_id_field'], 'gr2t2f', false)
            ->addTable('gems__treatments', ['treatmentFieldValue.gr2t2f_value' => 'gtrt_id_treatment'], 'gtrt', false);

        $this->addColumn(new \Zend_Db_Expr("
        CASE
            WHEN grc_success = 1 AND gr2t_active = 1 AND (gr2t_end_date IS NULL OR gr2t_end_date > NOW()) THEN 1
            ELSE 0
        END"), 'success');

        $this->addColumn(new \Zend_Db_Expr("
        CASE
            WHEN gr2t_reception_code = '".\GemsEscort::RECEPTION_OK."' THEN 1
            ELSE 0
        END"), 'primaryTrack');

    }

    public function afterRegistry()
    {
        $this->set('patient', ['label' => 'patient']);
        $this->addTransformer(new PatientReferenceTransformer());
        $this->addTransformer(new PatientNameTransformer());
        $this->addTransformer(new FlatRespondentTrackFieldTransformer());
        $this->addTransformer(new BooleanTransformer(['primaryTrack', 'success']));
        $this->addTransformer(new AllNumberTransformer());

    }

    public function applyBrowseSettings()
    {
        $this->resetOrder();
        $this->set('gr2t_id_respondent_track', [
            'apiName' => 'id',
        ]);
        $this->set('gtr_id_track', [
            'label' => $this->_('Track id'),
            'apiName' => 'track',
        ]);

        $this->set('gtr_track_name', [
            'label' => $this->_('Track name'),
            'apiName' => 'trackName',
        ]);

        $this->set('success', [
            'label' => $this->_('success'),
            'apiName' => 'success',
        ]);

        $this->set('gdt_diagnosis_name', [
            'label' => $this->_('Diagnosis name'),
            'apiName' => 'diagnosisName',
        ]);

        $this->set('gtrt_name', [
            'label' => $this->_('Treatment name'),
            'apiName' => 'treatmentName',
        ]);

        $this->set('gr2o_patient_nr', [
            'apiName' => 'patientNr',
        ]);

        $this->set('gr2o_id_organization', [
            'apiName' => 'organizationId',
        ]);

        $this->set('gr2t_start_date', [
            'label' => $this->_('Start date'),
            'apiName' => 'trackStartDate',
        ]);

        $this->set('patientFullName', [
            'label' => $this->_('Patient name'),
            'apiName' => 'patientFullName',
        ]);

        $this->set('primaryTrack', [
            'label' => $this->_('Primary track'),
            'apiName' => 'primaryTrack',
        ]);

        $this->setKeys($this->_getKeysFor('gems__respondent2org'));
    }

    public function applyDiagnosisSort()
    {
        $this->addSort([
            'gdt_priority'             => SORT_ASC,
            'gr2t_start_date'          => SORT_DESC,
            'gr2t_reception_code'      => SORT_ASC,
            'gr2t_id_respondent_track' => SORT_DESC,
        ]);

    }
}
