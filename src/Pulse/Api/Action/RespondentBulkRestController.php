<?php


namespace Pulse\Api\Action;

use Gems\Model\EpisodeOfCareModel;
use Gems\Rest\Action\ModelRestController;
use Gems\Rest\Model\ModelProcessor;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Model\Emma\AgendaDiagnosisRepository;
use Pulse\Api\Model\Emma\AppointmentImportTranslator;
use Pulse\Api\Model\Emma\EpisodeOfCareImportTranslator;
use Pulse\Api\Model\Emma\OrganizationRepository;
use Pulse\Api\Model\Emma\RespondentImportTranslator;
use Pulse\Api\Model\Emma\RespondentRepository;
use Zalt\Loader\ProjectOverloader;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Expressive\Helper\UrlHelper;

class RespondentBulkRestController extends ModelRestController
{
    /**
     * @var \Gems_Agenda
     */
    protected $agenda;

    protected $agendaDiagnosisRepository;

    /**
     * @var Adapter
     */
    protected $db;

    /**
     * @var OrganizationRepository
     */
    protected $organizationRepository;

    /**
     * @var RespondentRepository
     */
    protected $respondentRepository;

    public function __construct(ProjectOverloader $loader, UrlHelper $urlHelper, Adapter $db,
                                AgendaDiagnosisRepository $agendaDiagnosisRepository,
                                OrganizationRepository $organizationRepository,
                                RespondentRepository $respondentRepository, \Gems_Agenda $agenda, $LegacyDb
    )
    {
        $this->agenda = $agenda;
        $this->agendaDiagnosisRepository = $agendaDiagnosisRepository;
        $this->db = $db;
        $this->organizationRepository = $organizationRepository;
        $this->respondentRepository = $respondentRepository;

        parent::__construct($loader, $urlHelper, $LegacyDb);
    }

    public function post(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($this->checkContentType($request) === false) {
            return new EmptyResponse(415);
        }

        $respondentRow = json_decode($request->getBody()->getContents(), true);

        if (empty($respondentRow)) {
            return new EmptyResponse(400);
        }

        $translator = new RespondentImportTranslator($this->db);
        $row = $translator->translateRow($respondentRow, true);

        $organizations = $this->organizationRepository->getOrganizationTranslations($row['organizations']);

        $processor = new ModelProcessor($this->loader, $this->model, $this->userId);

        $usersPerOrganization = [];
        foreach($organizations as $organizationId => $organizationName) {
            $row['gr2o_id_organization'] = $organizationId;
            // Add location!
            // Add Treatment if possible?

            $new = true;
            if ($patientId = $this->respondentRepository->getPatientId($row['gr2o_patient_nr'], $organizationId)) {
                $new = false;
                $row['gr2o_id_user'] = $row['grs_id_user'] = $patientId;
            }
            $this->model->applyEditSettings($new);

            try {
                $newRow = $processor->save($row, !$new);
            } catch(\Exception $e) {
                // Row could not be saved.
                // return JsonResponse
            }

            if (isset($newRow['grs_id_user'])) {
                $usersPerOrganization[$organizationId] = $newRow['grs_id_user'];
            }
        }

        $this->processEpisodes($newRow, $usersPerOrganization);
        $this->processAppointments($newRow, $usersPerOrganization);



        // Return the route as a link in the header, like in ModelRestControllerAbstract->saveRow()

        return new EmptyResponse(201);
    }

    protected function processAppointments($row, $usersPerOrganization)
    {
        $appointments = $row['appointments'];

        $appointmentModel = $this->loader->create('Model_AppointmentModel');

        $translator = new AppointmentImportTranslator($this->db, $this->agenda);

        foreach($appointments as $appointment) {

            if (!isset($appointment['id'])) {
                // Skipping appointment because no ID is set!
                continue;
            }

            if (!array_key_exists('organization', $appointment)) {
                // Skipping appointment because organization is not set in appointment!
                continue;
            }

            $organizationId = $this->organizationRepository->getOrganizationId($appointment['organization']);

            if ($organizationId === null) {
                // Skipping appointment because organization ID could not be found!
                continue;
            }
            // Creating a clone so original data is kept
            $appointmentData = $appointment;

            $appointmentData['gap_id_organization'] = $organizationId;
            $appointmentData['gap_id_user']         = $usersPerOrganization[$organizationId];

            $appointmentData = $translator->translateRow($appointmentData, true);

            $appointmentModel->applyEditSettings($appointmentData['gap_id_organization']);

            $processor = new ModelProcessor($this->loader, $appointmentModel, $this->userId);
            try {
                $processor->save($appointmentData, false);
            } catch(\Exception $e) {
                // Row could not be saved.
                // return JsonResponse
            }
        }
    }

    protected function processEpisodes($row, $usersPerOrganization)
    {
        $rawEpisodes = $row['episodes'];

        $translator = new EpisodeOfCareImportTranslator($this->db, $this->agendaDiagnosisRepository, $this->organizationRepository, $this->agenda);
        $episodesOfCare = $translator->translateEpisodes($rawEpisodes, $usersPerOrganization);

        $episodeModel = $this->loader->create('Model\\EpisodeOfCareModel');

        foreach($episodesOfCare as $episode) {
            $processor = new ModelProcessor($this->loader, $episodeModel, $this->userId);

            $update = isset($episode['gec_episode_of_care_id']);

            try {
                $processor->save($episode, $update);
            } catch(\Exception $e) {
                // Row could not be saved.
                // return JsonResponse
            }
        }
    }
}