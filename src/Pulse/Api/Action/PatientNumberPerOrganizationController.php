<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\ModelRestController;
use Gems\Rest\Exception\RestException;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class PatientNumberPerOrganizationController extends ModelRestController
{
    /**
     * Get one or multiple rows from the model
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return EmptyResponse|JsonResponse
     */
    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $queryParams = $request->getQueryParams();
        $patientNr = $request->getAttribute('gr2o_patient_nr');
        $organizationId = $request->getAttribute('gr2o_id_organization');
        if ($patientNr === null) {
            if (!isset($queryParams['gr2o_patient_nr'])) {
                throw new RestException('Patient numbers needs a PatientNr as the first parameter', 1, 'patient_nr_missing', 400);
            }
            $patientNr = $queryParams['gr2o_patient_nr'];

        }
        if ($organizationId === null) {

            if (!isset($queryParams['gr2o_id_organization'])) {
                throw new RestException('Patient numbers needs an Organization ID as the second parameter', 1, 'organization_id_missing', 400);
            }
            $organizationId = $queryParams['gr2o_id_organization'];
        }

        $currentRespondentFilter = [
            'gr2o_patient_nr' => $patientNr,
            'gr2o_id_organization' => $organizationId,
            'grc_success' => 1,
        ];

        $currentRespondent = $this->model->loadFirst($currentRespondentFilter);

        if ($currentRespondent && isset($currentRespondent['gr2o_id_user'])) {
            $currentRespondentId = $currentRespondent['gr2o_id_user'];
            $allPatientsFilter = [
                'gr2o_id_user' => $currentRespondentId,
                'grc_success' => 1,
                'gr2o_id_organization' => true,
            ];

            $allPatients = $this->model->load($allPatientsFilter);

            $patientNumbers = [];
            foreach($allPatients as $patient) {
                $patientNumbers[$patient['gr2o_id_organization']] = $patient['gr2o_patient_nr'];
            }

            return new JsonResponse($patientNumbers);
        }

        return new EmptyResponse(404);
    }
}