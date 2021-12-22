<?php


namespace Ichom\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Ichom\DiagnoseActionCreator;
use Ichom\Repository\Diagnosis2TreatmentRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Loader\ProjectOverloader;

class DiagnosisWizardController extends RestControllerAbstract
{
    /**
     * @var array List of allowed content types as input for write methods
     */
    protected $allowedContentTypes = ['application/json'];

    /**
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * @var Diagnosis2TreatmentRepository
     */
    protected $diagnosis2TreatmentRepository;

    /**
     * @var ProjectOverloader
     */
    protected $overLoader;

    /**
     * @var \Gems_Util
     */
    protected $util;

    public function __construct(Diagnosis2TreatmentRepository $diagnosis2TreatmentRepository, ProjectOverloader $overLoader, $LegacyCurrentUser, \Gems_Util $util)
    {
        $this->diagnosis2TreatmentRepository = $diagnosis2TreatmentRepository;
        $this->overLoader = $overLoader;
        $this->currentUser = $LegacyCurrentUser;
        $this->util = $util;
    }

    /**
     * Check if current content type is allowed for the current method
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function checkContentType(ServerRequestInterface $request)
    {
        $contentTypeHeader = $request->getHeaderLine('content-type');
        foreach ($this->allowedContentTypes as $contentType) {
            if (strpos($contentTypeHeader, $contentType) !== false) {
                return true;
            }
        }

        return false;
    }

    public function post(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($this->checkContentType($request) === false) {
            return new EmptyResponse(415);
        }

        $parsedBody = json_decode($request->getBody()->getContents(), true);

        if (empty($parsedBody)) {
            return new EmptyResponse(400);
        }

        if (!isset($parsedBody['patientNr'], $parsedBody['organizationId'])) {
            return new JsonResponse(['error' => 'missing_data', 'message' => 'Patient number or organization ID missing in post body'], 400);
        }

        $patientNr = $parsedBody['patientNr'];
        $organizationId = $parsedBody['organizationId'];

        $this->getUserAtributesFromRequest($request);
        \Gems_Model::setCurrentUserId($this->userId);

        $respondent = $this->overLoader->create('Tracker\\Respondent', $patientNr, $organizationId);

        $startStop = $this->overLoader->create('StartStop');

        $diagnosisData = $this->getCurrentDiagnosisData($respondent);

        $actionCreator = $this->createActionCreator($diagnosisData);

        $this->processActions($diagnosisData, $parsedBody, $actionCreator);

        $result = $startStop->updateDiagnoses($respondent->getId(), $organizationId, $actionCreator);

        return new JsonResponse($result, 201);
    }

    protected function createActionCreator($diagnosisData)
    {
        return $this->overLoader->create('DiagnoseActionCreator', $diagnosisData, $this->diagnosis2TreatmentRepository);
    }

    /**
     * @return array respondent_track_id => array
     */
    protected function getCurrentDiagnosisData(\Gems_Tracker_Respondent $respondent)
    {
        $otherOrganizations = $this->util->getOtherOrgsFor($respondent->getOrganizationId());
        if (! is_array($otherOrganizations)) {
            // Logic as defined in 'Gems_Util->getOtherOrgsFor()
            if (true === $otherOrganizations) {
                $otherOrganizations = array_keys($this->currentUser->getAllowedOrganizations());
            } else {
                $otherOrganizations = $respondent->getOrganizationId();
            }
        }

        $diagData = $this->diagnosis2TreatmentRepository->getCurrentDiagnosisTrackModel()->load([
            'gr2t_id_user' => $respondent->getId(),
            'gr2o_id_organization' => $otherOrganizations,
            'grc_success' => 1,
            'gr2t_active' => 1,
            new \Zend_Db_Expr('gr2t_end_date IS NULL OR gr2t_end_date > NOW()'),
        ], [
            'gdt_priority'             => SORT_ASC,
            'gr2t_start_date'          => SORT_DESC,
            'gr2t_id_respondent_track' => SORT_DESC,
        ]);
        $currentDiagnosisData = array_column($diagData, null, 'gr2t_id_respondent_track');

        if (!$currentDiagnosisData) {
            $currentDiagnosisData = [];
        }

        return $currentDiagnosisData;
    }

    protected function processActions($diagnosisData, $formData, DiagnoseActionCreator $actionCreator)
    {
        if (isset($formData['changed'])) {
            foreach($formData['changed'] as $respondentTrackId=>$data) {
                if (isset($diagnosisData[$respondentTrackId])) {
                    switch ($data['action']) {
                        case 'edit':
                            if ($data['diagnosis'] != $diagnosisData[$respondentTrackId]['gdt_id_diagnosis']) {
                                $error = isset($data['diagnosisChangeReason']) && $data['diagnosisChangeReason'] ? 'error-new-diagnosis' : 'stop-new-diagnosis';
                            } elseif ($data['treatment'] != $diagnosisData[$respondentTrackId]['gtrt_id_treatment']) {
                                $error = isset($data['treatmentChangeReason']) && $data['treatmentChangeReason'] ? 'error-new-treatment' : 'stop-new-treatment';
                            } else {
                                $error = null;
                            }

                            $actionCreator->changeTrack(
                                'change ' . $respondentTrackId,
                                $respondentTrackId,
                                $data['diagnosis'],
                                $data['treatment'],
                                $data,
                                $error
                            );
                            break;
                        case 'delete':
                            $actionCreator->addTrackRemoval($respondentTrackId, 1 == $data['removeDiagnosis'] ? 'error-end-diagnosis' : 'stop-end-diagnosis');
                            break;
                        default:
                            break;
                    }
                }
            }
        }
        if (isset($formData['new'])) {
            $oldTrackId = null;
            if (isset($formData['changed']) && count($formData['changed'])) {
                $oldTrackId = key($formData['changed']);
            }
            foreach($formData['new'] as $key=>$newData) {
                $actionCreator->addNewTrack(
                    $key,
                    $newData['track'],
                    $newData['diagnosis'],
                    $newData['treatment'],
                    $oldTrackId,
                    null,
                    $newData
                );
            }
        }
    }
}
