<?php

namespace Pulse\Api\Action;


use Gems\DataSetMapper\Exception\DataSetMissingDataException;
use Gems\DataSetMapper\Repository\DataSetRepository;
use Gems\Rest\Action\RestControllerAbstract;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Ichom\Model\Transformer\PatientNameTransformer;
use Pulse\Tracker\DossierTemplateRepository;

class RespondentDossierTemplatePreviewController extends RestControllerAbstract
{
    /**
     * @var DossierTemplateRepository
     */
    protected $dossierTemplateRepository;
    /**
     * @var DataSetRepository
     */
    protected $dataSetRepository;

    protected $intakeTrackCode = 'intake';

    public function __construct(DossierTemplateRepository $dossierTemplateRepository, DataSetRepository $dataSetRepository)
    {

        $this->dossierTemplateRepository = $dossierTemplateRepository;
        $this->dataSetRepository = $dataSetRepository;
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $params = $request->getQueryParams();
        if (!isset($params['patientNr'], $params['organizationId'])) {
            return new JsonResponse(['error' => 'missing_data', 'message' => 'Patient number or organization ID missing as query params']);
        }
        if (!(isset($params['diagnosis']) || isset($params['treatment']))) {
            return new JsonResponse(['error' => 'missing_data', 'message' => 'Diagnosis, treatment or both missing as query params']);
        }

        $diagnosis = null;
        if (isset($params['diagnosis'])) {
            $diagnosis = $params['diagnosis'];
        }
        $treatment = null;
        if (isset($params['treatment'])) {
            $treatment = $params['treatment'];
        }

        $dossierTemplate = $this->dossierTemplateRepository->getTemplateFromDiagnosisTreatment($diagnosis, $treatment);

        $extraFields = [];
        if (isset($params['side'])) {
            $extraFields['side'] = $params['side'];
        }
        if (isset($params['treatedFingers'])) {
            $extraFields['treatedFingers'] = $params['treatedFingers'];
        }

        if ($dossierTemplate) {
            $latestIntakeRespondentTrackId = $this->getLatestIntakeRespondentTrackId($params['patientNr'], $params['organizationId']);
            if ($latestIntakeRespondentTrackId) {
                try {
                    //$result = $this->getDiagnosisTreatmentData($diagnosis, $treatment);
                    $result = [];
                    $patientInfo = $this->getPatientInformation($params['patientNr'], $params['organizationId']);
                    $result['patientNr'] = $params['patientNr'];
                    $result['organizationId'] = $params['organizationId'];
                    $result['patientFullName'] = $patientInfo['patientFullName'];

                    $renderedTemplate = $this->dossierTemplateRepository->renderTemplate($dossierTemplate, $latestIntakeRespondentTrackId, $diagnosis, $treatment, $extraFields);
                    $result['dossierTemplate'] = $renderedTemplate;

                    return new JsonResponse($result);
                } catch (DataSetMissingDataException $e) {

                    // No action required
                    //\Mutil_Echo::track($e->getErrors());
                }

            }
        }

        return new EmptyResponse();
    }

    protected function getDiagnosisTreatmentData($diagnosisId, $treatmentId)
    {
        $result = [];
        if ($diagnosisId) {
            $diagnosisModel = new \Gems_Model_JoinModel('diagnosis', 'gems__diagnosis2track');
            $diagnosisModel->addTable('gems__tracks', ['gdt_id_track' => 'gtr_id_track']);
            $rawDiagnosisData = $diagnosisModel->loadFirst(['gdt_id_diagnosis' => $diagnosisId]);
            $result['diagnosisId'] = $diagnosisId;
            $result['diagnosisName'] = $rawDiagnosisData['gdt_diagnosis_name'];
            $result['trackName'] = $rawDiagnosisData['gtr_track_name'];
        }

        if ($treatmentId) {
            $treatmentModel = new \MUtil_Model_TableModel('gems__treatments');
            $rawTreatmentData = $treatmentModel->loadFirst(['gtrt_id_treatment' => $treatmentId]);
            $result['treatmentId'] = $treatmentId;
            $result['treatmentName'] = $rawTreatmentData['gtrt_name'];
        }

        return $result;
    }

    protected function getPatientInformation($patientNr, $organizationId)
    {
        $model = new \Gems_Model_JoinModel('respondentInformation', 'gems__respondent2org', 'gr2o', false);
        $model->addTable('gems__respondents', ['grs_id_user' => 'gr2o_id_user'], 'grs', false);

        $model->addTransformer(new PatientNameTransformer());

        $filter = [
            'gr2o_patient_nr' => $patientNr,
            'gr2o_id_organization' => $organizationId,
        ];

        return $model->loadFirst($filter);
    }

    protected function getLatestIntakeRespondentTrackId($patientNr, $organizationId)
    {
        $model = new \Gems_Model_JoinModel('RespondentTracks', 'gems__respondent2track', 'gr2t', false);
        $model->addTable('gems__respondent2org', ['gr2o_id_user' => 'gr2t_id_user', 'gr2o_id_organization' => 'gr2t_id_organization'], 'gr2o', false)
            ->addTable('gems__tracks', ['gr2t_id_track' => 'gtr_id_track'], false);
        $filter = [
            'gr2o_patient_nr' => $patientNr,
            'gr2t_id_organization' => $organizationId,
            'gtr_code' => $this->intakeTrackCode,
        ];
        $order = ['gr2t_start_date' => SORT_DESC];

        $latestIntakeTrack = $model->loadFirst($filter, $order);
        if ($latestIntakeTrack) {
            return $latestIntakeTrack['gr2t_id_respondent_track'];
        }
        return null;
    }
}
