<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\ModelRestController;
use Gems\Rest\Exception\RestException;
use Gems\Rest\Repository\AccesslogRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Loader\ProjectOverloader;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Expressive\Helper\UrlHelper;

class RespondentTrackRestController extends ModelRestController
{
    /**
     * @var \Gems_User_User
     */
    protected $currentUser;

    protected $stopReceptionCode = 'stop';

    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    public function __construct(AccesslogRepository $accesslogRepository, ProjectOverloader $loader, UrlHelper $urlHelper, $LegacyDb, \Gems_Tracker $tracker, \Zend_Locale $locale, $LegacyCurrentUser)
    {
        $this->currentUser = $LegacyCurrentUser;
        $this->tracker = $tracker;
        \Zend_Registry::set('Zend_Locale', $locale);
        parent::__construct($accesslogRepository, $loader, $urlHelper, $LegacyDb);

    }

    public function getList(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $queryParams = $request->getQueryParams();
        if (isset($queryParams['gr2o_patient_nr'], $queryParams['gr2o_id_organization'])) {
            $queryParams['gr2o_id_user'] = $this->getRespondentByPatientNr($queryParams['gr2o_patient_nr'], $queryParams['gr2o_id_organization']);
            unset($queryParams['gr2o_patient_nr']);
            unset($queryParams['gr2o_id_organization']);
            $queryParams['gr2t_id_organization'] = array_keys($this->currentUser->getAllowedOrganizations());

            $request = $request->withQueryParams($queryParams);
            return parent::getList($request, $delegate);
        }
        return new EmptyResponse(400);
    }

    protected function addNewModelRow($row)
    {
        if (!isset($row['gtr_id_track'])) {
            throw new RestException('Track not supplied', 400, 'missing_data');
        }
        if (!isset($row['gr2o_patient_nr'])) {
            throw new RestException('Track not supplied', 400, 'missing_data');
        }
        if (!isset($row['gr2o_id_organization'])) {
            throw new RestException('Track not supplied', 400, 'missing_data');
        }

        $filter = [
            'gtr_id_track'          => $row['gtr_id_track'],
            'gr2o_patient_nr'       => $row['gr2o_patient_nr'],
            'gr2o_id_organization'  => $row['gr2o_id_organization'],
        ];
        $row += $this->model->loadNew(null, $filter);
        return $row;
    }

    protected function afterSaveRow($newRow)
    {
        $changed = (boolean) $this->model->getChanged();
        $refresh = false;
        // Retrieve the key if just created
        if ($this->method == 'post') {
            $respondentTrack   = $this->tracker->getRespondentTrack($newRow);

            // Explicitly save the fields as the transformer in the model only handles
            // before save event (like default values) for existing respondenttracks
            $respondentTrack->setFieldData($newRow);

            $trackEngine = $this->tracker->getTrackEngine($newRow);

            // Create the actual tokens!!!!
            $trackEngine->checkRoundsFor($respondentTrack, $this->userId);
            $refresh = true;

        } elseif($changed) {
            // Check if the input has changed, i.e. one of the dates may have changed
            $respondentTrack   = $this->tracker->getRespondentTrack($newRow);
            $refresh = true;
        }

        if ($refresh) {
            // Perform a refresh from the database, to avoid date trouble
            $respondentTrack->refresh();
            $respondentTrack->checkTrackTokens($this->userId);
        }

        return parent::afterSaveRow($newRow);
    }

    /**
     * Do actions or translate the row before a save and before validators
     *
     * @param array $row
     * @return array
     */
    protected function beforeSaveRow($row)
    {
        return $row;
    }

    public function delete(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $idField = $this->getIdField();
        $id = $request->getAttribute($idField);

        if ($id === null || !$idField) {
            return new EmptyResponse(404);
        }

        $respondentTrack   = $this->tracker->getRespondentTrack($id);

        if (!$respondentTrack instanceof \Gems_Tracker_RespondentTrack) {
            return new EmptyResponse(404);
        }

        $parsedBody = json_decode($request->getBody()->getContents(), true);
        $comment = '';
        if (is_array($parsedBody) && array_key_exists('comment', $parsedBody)) {
            $comment = $parsedBody['comment'];
        }

        $result = $respondentTrack->setReceptionCode($this->stopReceptionCode, $comment, $this->userId);

        if ($result) {
            $now = new \DateTime;
            $respondentTrack->setEndDate($now->format('Y-m-d H:i:s'), $this->userId);
        }

        if ($result) {
            return new EmptyResponse(202);
        }

        return new EmptyResponse(204);
    }

    protected function getRespondentByPatientNr($patientNr, $organizationId)
    {
        $select = $this->db1->select();
        $select->from('gems__respondent2org', ['gr2o_id_user'])
            ->where('gr2o_patient_nr = ?', $patientNr)
            ->where('gr2o_id_organization = ?', $organizationId);

        return $this->db1->fetchOne($select);


    }
}