<?php


namespace Pulse\Api\Action;

use Gems\Model\EpisodeOfCareModel;
use Gems\Rest\Action\ModelRestController;
use Gems\Rest\Model\ModelException;
use Gems\Rest\Model\ModelProcessor;
use Gems\Rest\Model\ModelValidationException;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
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
use Zend\Diactoros\Response\JsonResponse;
use Zend\Expressive\Helper\UrlHelper;

class RespondentBulkRestController extends ModelRestController
{
    /**
     * @var \Gems_Agenda
     */
    protected $agenda;

    /**
     * @var AgendaDiagnosisRepository
     */
    protected $agendaDiagnosisRepository;

    /**
     * @var Adapter
     */
    protected $db;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Gems_Model
     */
    protected $modelLoader;

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
                                RespondentRepository $respondentRepository,
                                \Gems_Agenda $agenda,
                                \Gems_Model $modelLoader,
                                $EmmaImportLogger,
                                $LegacyDb
    )
    {

        $this->agenda = $agenda;
        $this->agendaDiagnosisRepository = $agendaDiagnosisRepository;
        $this->db = $db;
        $this->logger = $EmmaImportLogger;
        $this->modelLoader = $modelLoader;
        $this->organizationRepository = $organizationRepository;
        $this->respondentRepository = $respondentRepository;

        parent::__construct($loader, $urlHelper, $LegacyDb);
    }

    protected function createModel()
    {
        $model =  parent::createModel();
        $idField = 'grs_id_user';
        $model->setAutoSave($idField);

        // Make sure the fields get a userid when empty
        $model->setOnSave($idField, array($this->modelLoader, 'createGemsUserId'));

        return $model;
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

        if (!array_key_exists('patient_nr', $respondentRow) && !array_key_exists('gr2o_patient_nr', $respondentRow)) {
            return new JsonResponse(['error' => 'missing_data', 'Missing patient nr in patient_nr or gr2o_patient_nr field'], 400);
        }
        $patientNr = null;
        if (isset($respondentRow['patient_nr'])) {
            $patientNr = $respondentRow['patient_nr'];
        } elseif (isset($respondentRow['gr2o_patient_nr'])) {
            $patientNr = $respondentRow['gr2o_patient_nr'];
        }


        //$this->logger->debug('Starting import of bulk respondent', ['PatientNr' => $patientNr]);
        $this->logger->debug('Starting import of bulk respondent', $respondentRow);

        $translator = new RespondentImportTranslator($this->db, $this->logger);
        $row = $translator->translateRow($respondentRow, true);

        $organizations = $this->organizationRepository->getOrganizationTranslations($row['organizations']);

        if (empty($organizations)) {
            $message = sprintf('Skipping patient import because no organizations have been found in Pulse for %s', $patientNr);
            $this->logger->notice($message);
            return new JsonResponse(['message' => $message]);
        }

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

                if ($e instanceof ModelValidationException) {
                    $this->logger->error($e->getMessage(), $e->getErrors());
                    return new JsonResponse(['error' => 'validation_error', 'message' => $e->getMessage(), 'errors' => $e->getErrors()], 400);
                }

                if ($e instanceof ModelException) {
                    $this->logger->error($e->getMessage());
                    return new JsonResponse(['error' => 'model_error', 'message' => $e->getMessage()], 400);
                }

                // Unknown exception!
                return new EmptyResponse(400);
            }

            if (isset($newRow['grs_id_user'])) {
                $usersPerOrganization[$organizationId] = $newRow['grs_id_user'];
            }
        }


        $this->processEpisodes($newRow, $usersPerOrganization);
        $this->processAppointments($newRow, $usersPerOrganization);



        // Return the route as a link in the header, like in ModelRestControllerAbstract->saveRow()
        $this->logger->notice(sprintf('Finished import of bulk respondent %s', $patientNr));

        return new EmptyResponse(201);
    }

    protected function processAppointments($row, $usersPerOrganization)
    {
        $appointments = $row['appointments'];

        $appointmentModel = $this->loader->create('Model_AppointmentModel');

        $translator = new AppointmentImportTranslator($this->db, $this->agenda);

        foreach($appointments as $appointment) {

            if (!isset($appointment['id']) && !isset($appointment['gap_id_in_source'])) {
                // Skipping appointment because no ID is set!
                $this->logger->warning(sprintf('Skipping import of appointment because no id is set in appointment'), $appointment);
                continue;
            }

            if (!array_key_exists('organization', $appointment)) {
                // Skipping appointment because organization is not set in appointment!
                $this->logger->warning(sprintf('Skipping import of appointment because no organization is set in appointment'), $appointment);
                continue;
            }

            $organizationId = $this->organizationRepository->getOrganizationId($appointment['organization']);

            if ($organizationId === null) {
                // Skipping appointment because organization ID could not be found!
                $this->logger->warning(sprintf('Skipping import of appointment because appointment organization is unknown in pulse'), $appointment);
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

                if ($e instanceof ModelValidationException) {
                    $this->logger->error($e->getMessage(), $e->getErrors());
                    return new JsonResponse(['error' => 'validation_error', 'message' => $e->getMessage(), 'errors' => $e->getErrors()], 400);
                }

                if ($e instanceof ModelException) {
                    $this->logger->error($e->getMessage());
                    return new JsonResponse(['error' => 'model_error', 'message' => $e->getMessage()], 400);
                }

                // Unknown exception!
                return new EmptyResponse(400);
            }

            $this->logger->debug(sprintf('Appointment %s has successfully been imported.', $appointment['id']));

        }
    }

    protected function processEpisodes($row, $usersPerOrganization)
    {
        $rawEpisodes = $row['episodes'];

        $translator = new EpisodeOfCareImportTranslator($this->db, $this->agendaDiagnosisRepository, $this->logger, $this->organizationRepository, $this->agenda);
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

                if ($e instanceof ModelValidationException) {
                    $this->logger->error($e->getMessage(), $e->getErrors());
                    return new JsonResponse(['error' => 'validation_error', 'message' => $e->getMessage(), 'errors' => $e->getErrors()], 400);
                }

                if ($e instanceof ModelException) {
                    $this->logger->error($e->getMessage());
                    return new JsonResponse(['error' => 'model_error', 'message' => $e->getMessage()], 400);
                }

                // Unknown exception!
                return new EmptyResponse(400);
            }

            $action = 'created';
            if ($update) {
                $action = 'updated';
            }
            $this->logger->debug(sprintf('Episode %s has successfully been %s.', $episode['gec_id_in_source'], $action));
        }
    }
}