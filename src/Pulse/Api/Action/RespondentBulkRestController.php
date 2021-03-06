<?php


namespace Pulse\Api\Action;

use Gems\Model\EpisodeOfCareModel;
use Gems\Rest\Action\ModelRestController;
use Gems\Rest\Log\Formatter\SimpleMulti;
use Gems\Rest\Model\ModelException;
use Gems\Rest\Model\ModelProcessor;
use Gems\Rest\Model\ModelTranslateException;
use Gems\Rest\Model\ModelValidationException;
use Gems\Rest\Repository\AccesslogRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Laminas\Log\PsrLoggerAdapter;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Pulse\Api\Model\Emma\AgendaDiagnosisRepository;
use Pulse\Api\Model\Emma\AppointmentImportTranslator;
use Pulse\Api\Model\Emma\AppointmentRepository;
use Pulse\Api\Model\Emma\EpisodeOfCareImportTranslator;
use Pulse\Api\Model\Emma\OrganizationRepository;
use Pulse\Api\Model\Emma\RespondentImportTranslator;
use Pulse\Api\Model\Emma\RespondentRepository;
use Pulse\Log\AppointmentActivity;
use Zalt\Loader\ProjectOverloader;
use Laminas\Log\Writer\Stream;

use Laminas\Stdlib\SplPriorityQueue;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Helper\UrlHelper;

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
     * @var \Gems_Model_AppointmentModel
     */
    protected $appointmentModel;

    /**
     * @var AppointmentRepository
     */
    protected $appointmentRepository;

    /**
     * @var Adapter
     */
    protected $db;

    /**
     * @var LoggerInterface
     */
    protected $emmaRespondentErrorLogger;

    /**
     * @var EpisodeOfCareModel
     */
    protected $episodeModel;

    /**
     * @var \Gems_Loader
     */
    protected $legacyLoader;

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

    public function __construct(AccesslogRepository $accesslogRepository, ProjectOverloader $loader, UrlHelper $urlHelper, Adapter $db,
                                AgendaDiagnosisRepository $agendaDiagnosisRepository,
                                AppointmentRepository $appointmentRepository,
                                OrganizationRepository $organizationRepository,
                                RespondentRepository $respondentRepository,
                                \Gems_Agenda $agenda,
                                \Gems_Model $modelLoader,
                                \Gems_Loader $legacyLoader,
                                $EmmaImportLogger,
                                $EmmaRespondentErrorLogger,
                                $LegacyDb
    )
    {

        $this->agenda = $agenda;
        $this->agendaDiagnosisRepository = $agendaDiagnosisRepository;
        $this->appointmentRepository = $appointmentRepository;
        $this->db = $db;
        $this->logger = $EmmaImportLogger;
        $this->emmaRespondentErrorLogger = $EmmaRespondentErrorLogger;
        $this->modelLoader = $modelLoader;
        $this->organizationRepository = $organizationRepository;
        $this->respondentRepository = $respondentRepository;
        $this->legacyLoader = $legacyLoader;

        parent::__construct($accesslogRepository, $loader, $urlHelper, $LegacyDb);
    }

    protected function checkLoggers(ServerRequestInterface $request)
    {
        $currentUserName = $request->getAttribute('user_name');

        if (strpos(strtolower($currentUserName), 'heuvelrug') !== false) {

            $importWriter = new Stream(GEMS_LOG_DIR . '/heuvelrug-import.log');
            $importWriter->setFormatter(new SimpleMulti());

            $importWriterQueue = new SplPriorityQueue();
            $importWriterQueue->insert($importWriter, 1);

            $importLogger = $this->logger->getLogger();
            $importLogger->setWriters($importWriterQueue);
            $this->logger = new PsrLoggerAdapter($importLogger);

            $respondentErrorWriter = new Stream(GEMS_LOG_DIR . '/heuvelrug-respondent-error.log');
            $respondentErrorWriter->setFormatter(new SimpleMulti());

            $respondentErrorWriterQueue = new SplPriorityQueue();
            $respondentErrorWriterQueue->insert($respondentErrorWriter, 1);
            $emmaImportLogger = $this->emmaRespondentErrorLogger->getLogger();
            $emmaImportLogger->setWriters($respondentErrorWriterQueue);
            $this->emmaRespondentErrorLogger = new PsrLoggerAdapter($emmaImportLogger);
        }
    }

    protected function createModel()
    {
        $model =  parent::createModel();

        if ($model instanceof \MUtil_Model_ModelAbstract) {
            $idField = 'grs_id_user';
            $model->setAutoSave($idField);

            // Make sure the fields get a userid when empty
            $model->setOnSave($idField, array($this->modelLoader, 'createGemsUserId'));
        }

        return $model;
    }

    protected function getAppointmentModel()
    {
        if ($this->appointmentModel instanceof \MUtil_Model_ModelAbstract) {
            return $this->appointmentModel;
        }
        return $this->loader->create('Model_AppointmentModel');
    }

    /**
     * @return AppointmentActivity
     */
    protected function getAppointmentActivityLogger()
    {
        return $this->legacyLoader->getAppointmentActivityLogger();
    }

    protected function getEpisodeModel()
    {
        if ($this->episodeModel instanceof \MUtil_Model_ModelAbstract) {
            return $this->episodeModel;
        }
        return $this->loader->create('Model\\EpisodeOfCareModel');
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
            return new JsonResponse(['error' => 'missing_data', 'message' => 'Missing patient nr in patient_nr or gr2o_patient_nr field'], 400);
        }

        if (!array_key_exists('organizations', $respondentRow)) {
            return new JsonResponse(['error' => 'missing_data', 'message' => 'Missing organizations array'], 400);
        }
        $patientNr = null;
        if (isset($respondentRow['patient_nr'])) {
            $patientNr = $respondentRow['patient_nr'];
        } elseif (isset($respondentRow['gr2o_patient_nr'])) {
            $patientNr = $respondentRow['gr2o_patient_nr'];
        }

        $this->checkLoggers($request);


        $currentUserName = $request->getAttribute('user_name');

        //$this->logger->debug('Starting import of bulk respondent', ['PatientNr' => $patientNr]);
        $this->logger->debug('Starting import of bulk respondent by ' . $currentUserName, $respondentRow);

        $translator = new RespondentImportTranslator($this->respondentRepository, $this->logger, $this->emmaRespondentErrorLogger, $currentUserName);
        $row = $translator->translateRowOnce($respondentRow, true);

        $organizations = $this->organizationRepository->getOrganizationTranslations($row['organizations']);

        if (empty($organizations)) {
            $message = sprintf('Skipping patient import because no organizations have been found in Pulse for %s', $patientNr);
            $this->logger->notice($message);
            return new JsonResponse(['message' => $message]);
        }

        //$this->model->copyKeys();

        /*$processor = new ModelProcessor($this->loader, $this->model, $this->userId);
        $processor->setAddDefaults(true);*/

        $usersPerOrganization = [];
        foreach($organizations as $organizationId => $organizationName) {
            $row['gr2o_id_organization'] = $organizationId;

            try {
                $patientRow = $translator->matchRowToExistingPatient($row, $this->model);

            } catch(ModelTranslateException $e) {
                $this->logger->error($e->getMessage());
                return new JsonResponse(['error' => 'model_translation_error', 'message' => $e->getMessage()], 400);
            } catch(ModelException $e) {
                return new EmptyResponse(200);
            }

            $new = true;
            if (array_key_exists('new_respondent', $patientRow)) {
                $new = $patientRow['new_respondent'];
            }

            if ($new) {
                $this->logger->debug(sprintf('Respondent \'%s\' is new', $patientNr));
            } else {
                $this->logger->debug(sprintf('Respondent \'%s\' exists', $patientNr));
            }

            $this->model->applyEditSettings($new);

            $processor = new ModelProcessor($this->loader, $this->model, $this->userId, $this->logger);
            $processor->setAddDefaults(true);

            if ($new) {
                $locationName = $this->organizationRepository->getLocationFromOrganizationName($organizationName);
                $location = null;
                if ($locationName) {
                    $location = $this->agenda->matchLocation($locationName, $organizationId, false);
                }
                if ($location && isset($location['glo_id_location'])) {
                    $patientRow['gr2o_id_location'] = (int)$location['glo_id_location'];
                }
            }

            try {
                $newRow = $processor->save($patientRow, !$new);
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
                $this->logger->error($e->getMessage());
                return new JsonResponse(['error' => 'unknown_error', 'message' => $e->getMessage()], 400);
            }

            if (isset($newRow['grs_id_user'])) {
                $usersPerOrganization[$organizationId] = $newRow['grs_id_user'];
            }
        }

        $episodeResult = $this->processEpisodes($newRow, $usersPerOrganization);
        $appointmentResult = $this->processAppointments($newRow, $usersPerOrganization);

        if ($this->modelLoader instanceof \Pulse_Model) {
            $this->processPpAndAnesthesia($newRow, $usersPerOrganization);
        }

        // Return the route as a link in the header, like in ModelRestControllerAbstract->saveRow()
        $this->logger->notice(sprintf('Finished import of bulk respondent %s', $patientNr));

        return new EmptyResponse(201);
    }

    protected function processAppointments($row, $usersPerOrganization)
    {
        if (!array_key_exists('appointments', $row)) {
            $this->logger->info('No appointments to import');
            return;
        }

        $appointments = $row['appointments'];

        $appointmentModel = $this->getAppointmentModel();
        //$appointmentModel->del('gap_id_procedure');

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
            $appointmentData['location']            = $this->organizationRepository->getLocationFromOrganizationName($appointment['organization']);

            $appointmentData = $translator->translateRow($appointmentData, true);

            $appointmentModel->applyEditSettings($appointmentData['gap_id_organization']);

            $processor = new ModelProcessor($this->loader, $appointmentModel, $this->userId);

            $new = $this->checkExistingAppointment($appointmentData, $processor);

            try {

                $processor->setAddDefaults($new);
                $newAppointmentData = $processor->save($appointmentData, !$new);

                if ($processor->getChanged() && !$new) {
                    $admissionTime = $newAppointmentData['gap_admission_time'];
                    if ($admissionTime instanceof \MUtil_Date) {
                        $admissionTime = $admissionTime->toString('yyyy-MM-dd HH:mm:ss');
                    }
                    $appointmentActivityLogger = $this->getAppointmentActivityLogger();
                    $appointmentActivityLogger->logAppointmentChange(
                        $newAppointmentData['gap_id_appointment'],
                        $row['gr2o_patient_nr'],
                        $newAppointmentData['gap_id_user'],
                        $newAppointmentData['gap_id_organization'],
                        $new,
                        $admissionTime
                    );
                }

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
                $this->logger->error($e->getMessage());
                return new EmptyResponse(400);
            }

            //$appointmentObject = $this->agenda->getAppointment($newAppointmentData);
            //$appointmentObject->updateTracks();

            $this->logger->debug(sprintf('Appointment %s has successfully been imported.', $appointment['id']));
        }
        return true;
    }

    protected function checkExistingAppointment(array $appointmentData, ModelProcessor $processor)
    {
        $existingAppointmentData = $this->appointmentRepository->getAppointmentDataBySourceId($appointmentData['gap_id_in_source'], 'emma');
        $new = !(boolean) $existingAppointmentData;

        if ($existingAppointmentData !== false) {
            $admissionTime = $appointmentData['gap_admission_time'];
            if (substr($admissionTime, -1) === 'Z') {
                $admissionTime = substr($admissionTime, 0, -1);
            }
            $existingAppointmentDate = new \MUtil_Date($existingAppointmentData['gap_admission_time'], 'yyyy-MM-dd HH:mm:ss');
            $newAppointmentDate = new \MUtil_Date($admissionTime, 'yyyy-MM-dd HH:mm:ss');


            if (($existingAppointmentDate->getYear() != $newAppointmentDate->getYear()) || ($existingAppointmentDate->getMonth() != $newAppointmentDate->getMonth()) || ($existingAppointmentDate->getDay() != $newAppointmentDate->getDay())) {
                $currentAppointmentVersion = $this->appointmentRepository->getLatestAppointmentVersion($appointmentData['gap_id_in_source'], 'emma');
                $canceledSourceId = $appointmentData['gap_id_in_source'] . $this->appointmentRepository->sourceVersionSuffix . ($currentAppointmentVersion + 1);

                $existingAppointmentData['gap_id_in_source'] = $canceledSourceId;
                $existingAppointmentData['gap_status'] = $this->appointmentRepository->canceledAppointmentStatus;
                $existingAppointmentData['gap_comment'] = 'Version ' . ($currentAppointmentVersion+1) . ' duplicate canceled because appointment date has changed in EMMA.';

                $processor->setAddDefaults($new);

                try {
                    $processor->save($existingAppointmentData, true);
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
                $new = true;
            }
        }


        // Return if the appointment should be new
        return $new;
    }

    protected function processEpisodes($row, $usersPerOrganization)
    {
        if (!array_key_exists('episodes', $row)) {
            $this->logger->info('No episodes to import');
            return;
        }
        $rawEpisodes = $row['episodes'];

        $translator = new EpisodeOfCareImportTranslator($this->db, $this->agendaDiagnosisRepository, $this->logger, $this->organizationRepository, $this->agenda);
        $episodesOfCare = $translator->translateEpisodes($rawEpisodes, $usersPerOrganization);

        $episodeModel = $this->getEpisodeModel();
        $processor = new ModelProcessor($this->loader, $episodeModel, $this->userId);
        $processor->setAddDefaults(false);


        foreach($episodesOfCare as $episode) {
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
                $this->logger->error($e->getMessage());
                return new EmptyResponse(400);
            }

            $action = 'created';
            if ($update) {
                $action = 'updated';
            }
            $this->logger->debug(sprintf('Episode %s has successfully been %s.', $episode['gec_id_in_source'], $action));
        }

        return true;
    }

    protected function processPpAndAnesthesia($newRow)
    {
        $patientNr = $newRow['gr2o_patient_nr'];
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->columns(['gr2o_id_organization', 'gr2o_id_user', 'gr2o_patient_nr'])
            ->join('gems__reception_codes', 'gr2o_reception_code = grc_id_reception_code', [])
            ->where(['grc_success' => 1, 'gr2o_patient_nr' => $patientNr]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        $respondents = iterator_to_array($result);

        foreach($respondents as $OkRespondents) {
            // Check PP
            $respondent = $this->legacyLoader->getRespondent($OkRespondents['gr2o_patient_nr'], $OkRespondents['gr2o_id_organization']);
            if ($respondent instanceof \Pulse_Tracker_Respondent) {
                $respondent->checkPp(false);
            }
            // Check Anesthesia
            $this->modelLoader->checkAnaesthesiaLink($OkRespondents['gr2o_id_user'], $OkRespondents['gr2o_id_organization']);
        }
    }



    public function setAppointmentModel(\Gems_Model_AppointmentModel $appointmentModel)
    {
        $this->appointmentModel = $appointmentModel;
    }

    public function setEpisodeModel(EpisodeOfCareModel $episodeModel)
    {
        $this->episodeModel = $episodeModel;
    }
}
