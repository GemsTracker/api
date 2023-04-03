<?php

namespace Pulse\Api\Action;

use Gems\Rest\Legacy\CurrentUserRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;

class AnesthesiaCheckHandler implements MiddlewareInterface
{

    public const CHECK_ANESTHESIA = 'anesthesia';

    public const CHECK_POS = 'pos';

    protected \Gems_Model $modelLoader;

    protected \Zend_Translate_Adapter $translateAdapter;
    private \Gems_Tracker $tracker;
    private \Zend_Locale $locale;

    protected $currentUserId;

    protected $defaultCheckType = self::CHECK_ANESTHESIA;

    protected $surveyFormQuestions = [
        self::CHECK_ANESTHESIA => [
            'asaClassificatie',
            'statusConsent',
        ],
        self::CHECK_POS => [
            'statusConsent',
            'opmerkingenPOS',
        ]
    ];
    private Adapter $db;

    public function __construct(
        \Zend_Translate_Adapter $translateAdapter,
        \Gems_Model $modelLoader,
        \Gems_Tracker $tracker,
        \Zend_Locale $locale,
        CurrentUserRepository $currentUserRepository,
        Adapter $db
    )
    {
        $this->translateAdapter = $translateAdapter;
        $this->modelLoader = $modelLoader;
        $this->tracker = $tracker;
        $this->locale = $locale;
        $this->currentUserId = $currentUserRepository->getCurrentUser()->getUserId();
        $this->db = $db;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $tokenId = $request->getAttribute('id');
        $checkType = $this->getCheckType($request);
        if ($tokenId === null) {
            return new JsonResponse([
                'error' => 'Token not found',
            ], 404);
        }
        if ($request->getMethod() === 'PATCH') {
            $parsedBody = json_decode($request->getBody()->getContents(), true);
            try {
                $this->saveTokenData($tokenId, $checkType, $parsedBody);
            } catch (\Exception $e) {
                return new JsonResponse(['error' => $e->getMessage()], 500);
            }
            return new EmptyResponse(200);
        }

        return new JsonResponse($this->getFormStructure($tokenId, $checkType));
    }

    protected function getCheckType(ServerRequestInterface $request)
    {
        $queryParams = $request->getQueryParams();
        if (!isset($queryParams['type'])) {
            return $this->defaultCheckType;
        }
        if ($queryParams['type'] !== static::CHECK_ANESTHESIA && $queryParams['type'] !== static::CHECK_POS) {
            return $this->defaultCheckType;
        }
        return $queryParams['type'];
    }

    protected function getFormStructure($tokenId, $checkType)
    {
        if (!$this->modelLoader instanceof \Pulse_Model) {
            return [];
        }

        $structure = [];
        if ($checkType === static::CHECK_ANESTHESIA) {
            $structure = [
                'anesthesiaStatus' => [
                    'label' => $this->translateAdapter->_('Anesthesia status'),
                    'type' => 'string',
                    'required' => false,
                    'name' => 'anesthesiaStatus',
                    'multiOptions' => $this->getAnesthesiaStatuses(),
                    'elementClass' => 'radio',
                ],
            ];
        }

        $surveyInformation = $this->tracker->getToken($tokenId)->getSurvey()->getQuestionInformation($this->locale->getLanguage());

        $surveyFormQuestions = $this->getSurveyFormQuestions($checkType);

        foreach($surveyFormQuestions as $surveyFormQuestion => $questionType) {
            if (is_int($surveyFormQuestion)) {
                $surveyFormQuestion = $questionType;
                $questionType = null;
            }
            if (isset($surveyInformation[$surveyFormQuestion])) {
                $structure[$surveyFormQuestion] = $this->getSurveyFormStructure($surveyFormQuestion, $surveyInformation[$surveyFormQuestion], $questionType);
            }
        }

        $structure['anesthesiaComment'] = [
            'label' => $this->translateAdapter->_('Screening anaesthetist'),
            'type' => 'string',
            'required' => false,
            'name' => 'anesthesiaComment',
            'elementClass' => 'textarea',
            'rows' => 10,
        ];

        return $structure;
    }

    public function getAnesthesiaStatuses()
    {
        $statusFilters = $this->modelLoader->getStatusFilters();
        return [
            \Pulse_Model::STATUS_OK => $statusFilters[\Pulse_Model::STATUS_OK],
            \Pulse_Model::STATUS_REQUEST_INFO => $statusFilters[\Pulse_Model::STATUS_REQUEST_INFO],
            \Pulse_Model::STATUS_CHECKED => $statusFilters[\Pulse_Model::STATUS_CHECKED],
            \Pulse_Model::STATUS_REJECTED => $statusFilters[\Pulse_Model::STATUS_REJECTED],
        ];
    }

    protected function getSurveyFormQuestions($type)
    {
        return $this->surveyFormQuestions[$type] ?? [];
    }

    protected function getSurveyFormStructure($questionCode, $questionInformation, $type = null)
    {
        $structure = [
            'label' => $questionInformation['question'] ?? $questionCode,
            'type' => 'string',
            'required' => false,
            'name' => $questionCode,
            'elementClass' => 'textarea',
            'rows' => 5,
        ];

        if (isset($questionInformation['answers']) && is_array($questionInformation['answers'])) {
            $structure['multiOptions'] = $questionInformation['answers'];
            $structure['elementClass'] = 'radio';
        }

        return $structure;
    }

    protected function saveTokenData($tokenId, $checkType, $data)
    {
        $checkTokens = false;
        $token = $this->tracker->getToken($tokenId);
        if ($token->exists && $token->isCompleted()) {
            $anesthesiaStatuses = $this->getAnesthesiaStatuses();
            $oldReceptionCode = $token->getReceptionCode()->getCode();
            $oldComment = $token->getComment();
            if (isset($data['anesthesiaStatus'], $anesthesiaStatuses[$data['anesthesiaStatus']])
                && $oldReceptionCode !== $data['anesthesiaStatus']) {


                $comment = null;
                if (isset($data['anesthesiaComment'])) {
                    $comment = $data['anesthesiaComment'];
                }
                $token->setReceptionCode($data['anesthesiaStatus'], $comment, $this->currentUserId);
                $this->logTokenStatusChange($token, 'anesthesiaStatus', $oldReceptionCode, $data['anesthesiaStatus']);
                if ($comment !== null && $comment !== $oldComment) {
                    $this->logTokenStatusChange($token, 'anesthesiaComment', $oldComment, $comment);
                }
                $checkTokens = true;

            } elseif (isset($data['anesthesiaComment']) && $oldComment !== $data['anesthesiaComment']) {
                $token->setComment($data['anesthesiaComment'], $this->currentUserId);
                $this->logTokenStatusChange($token, 'anesthesiaComment', $oldComment, $data['anesthesiaComment']);
            }

            $surveyInformation = $token->getSurvey()->getQuestionInformation($this->locale->getLanguage());
            $currentAnswers = $token->getRawAnswers();

            $surveyAnswers = [];
            $surveyFormQuestions = $this->getSurveyFormQuestions($checkType);

            foreach($surveyFormQuestions as $surveyFormQuestion) {
                if (isset(
                        $data[$surveyFormQuestion],
                        $surveyInformation[$surveyFormQuestion]
                    )
                    && array_key_exists($surveyFormQuestion, $currentAnswers)
                    && $currentAnswers[$surveyFormQuestion] != $data[$surveyFormQuestion]) {
                    if (isset($surveyInformation[$surveyFormQuestion]['answers'])
                        && is_array($surveyInformation[$surveyFormQuestion]['answers'])
                        && !isset($surveyInformation[$surveyFormQuestion]['answers'][$data[$surveyFormQuestion]])) {
                        continue;
                    }
                    $surveyAnswers[$surveyFormQuestion] = $data[$surveyFormQuestion];
                }
            }

            if (count($surveyAnswers)) {
                $token->setAndLogRawAnswers($surveyAnswers);
                $checkTokens = true;
                if ($token instanceof \Pulse_Tracker_Token) {
                    $token->setResult($surveyAnswers, $this->currentUserId);
                }
            }
        }

        if ($checkTokens) {
            $token->getTrackEngine()->checkTokensFrom($token->getRespondentTrack(), $token, $this->currentUserId);
            //$token->checkTokenCompletion($this->currentUserId);
        }
    }

    protected function logTokenStatusChange(\Gems_Tracker_Token $token, $code, $oldValue, $newValue)
    {
        $now = new \DateTimeImmutable();
        $logData = [
            'plta_id_token' => $token->getTokenId(),
            'plta_question_code' => $code,
            'plta_old_value' => $oldValue,
            'plta_new_value' => $newValue,
            'plta_created' => $now->format('Y-m-d H:i:s'),
            'plta_created_by' => $this->currentUserId,
            'plta_context' => 'Anesthesie controleer scherm',
        ];
        $table = new TableGateway('pulse__log_token_answers', $this->db);
        $table->insert($logData);
    }
}