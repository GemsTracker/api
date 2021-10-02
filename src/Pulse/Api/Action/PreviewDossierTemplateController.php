<?php

namespace Pulse\Api\Action;


use Gems\DataSetMapper\Exception\DataSetMissingDataException;
use Gems\Rest\Action\RestControllerAbstract;
use Gems\Rest\Exception\RestException;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Tracker\DossierTemplateRepository;

class PreviewDossierTemplateController extends RestControllerAbstract
{
    protected $intakeTrackCode = 'intake';
    /**
     * @var DossierTemplateRepository
     */
    protected $dossierTemplateRepository;

    public function __construct(DossierTemplateRepository $dossierTemplateRepository)
    {
        $this->dossierTemplateRepository = $dossierTemplateRepository;
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $params = $request->getQueryParams();

        if (!(array_key_exists('patient-nr', $params) && array_key_exists('organization', $params))) {
            throw new RestException('patient number and organization should be supplied ', 1, 'missing_filters', 400);
        }

        if (!(array_key_exists('diagnosis', $params) || array_key_exists('treatment', $params))) {
            throw new RestException('A diagnosis, treatment or both should be supplied ', 1, 'missing_filters', 400);
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
        if ($dossierTemplate) {
            $latestIntakeRespondentTrackId = $this->getLatestIntakeRespondentTrackId($params['patient-nr'], $params['organization']);
            if ($latestIntakeRespondentTrackId) {
                try {
                    $renderedTemplate = $this->dossierTemplateRepository->renderTemplate($dossierTemplate, $latestIntakeRespondentTrackId, $diagnosis, $treatment);

                    return new JsonResponse(['template' => $renderedTemplate]);
                } catch (DataSetMissingDataException $e) {
                    // No action required
                }

            }
        }

        return new EmptyResponse();
    }

    protected function getLatestIntakeRespondentTrackId($patientNr, $organizationId)
    {
        $model = new \Gems_Model_JoinModel('RespondentTracks', 'gems__respondent2track', 'gr2t', false);
        $model->addTable('gems__respondent2org', ['gr2o_id_user' => 'gr2t_id_user', 'gr2o_id_organization' => 'gr2t_id_organization'], false)
            ->addTable('gems__tracks', ['gr2t_id_track' => 'gtr_id_track'], false);

        $filter = [
            'gr2o_patient_nr' => $patientNr,
            'gr2o_id_organization' => $organizationId,
            'gtr_code' => $this->intakeTrackCode,
        ];
        $order = ['gr2t_start_date DESC'];

        $latestIntakeTrack = $model->loadFirst($filter, $order);
        if ($latestIntakeTrack) {
            return $latestIntakeTrack['gr2t_id_respondent_track'];
        }
        return null;
    }
}
