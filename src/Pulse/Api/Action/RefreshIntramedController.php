<?php

namespace Pulse\Api\Action;

use Gems\Rest\Action\RestControllerAbstract;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;

class RefreshIntramedController extends RestControllerAbstract
{
    /**
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * @var \Pulse_User_User
     */
    protected $currentUser;

    public function __construct(\Gems_Loader $loader, $LegacyCurrentUser)
    {
        $this->loader = $loader;
        $this->currentUser = $LegacyCurrentUser;
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $this->loadIntramedSettings();
        $params = $request->getQueryParams();

        $patientNr = null;
        $organizationId = null;

        if (isset($params['patient_nr'])) {
            $patientNr = $params['patient_nr'];
        } elseif (isset($params['gr2o_patient_nr'])) {
            $patientNr = $params['gr2o_patient_nr'];
        }

        if (isset($params['organization_id'])) {
            $organizationId = $params['organization_id'];
        } elseif (isset($params['gr2o_id_organization'])) {
            $organizationId = $params['gr2o_id_organization'];
        }

        if ($patientNr === null || $organizationId === null) {
            return new JsonResponse(['error' => 'missing_data', 'message' => 'Patient number or organization ID missing as query params']);
        }

        $organization = $this->loader->getOrganization($organizationId);

        /*if (!$organization->containsCode('intramed')) {
            return new EmptyResponse(204);
        }*/

        $onlyAppointments = null;

        try {
            $intramedClient = $this->loader->getIntramedClient($this->currentUser);
            $result = $intramedClient->updatePatient($patientNr, $organizationId, true, true);


            return new JsonResponse([
                'changes' => $result->getChanges(),
                'actions' => $result->getActions(),
            ]);
        } catch (\Pulse\Intramed\IntramedSetupException $e) {
            return new JsonResponse(['error' => $e->getMessage()]);
        }
    }

    protected function loadIntramedSettings()
    {
        require(GEMS_ROOT_DIR . '/config/intramedSettings.php');
    }
}
