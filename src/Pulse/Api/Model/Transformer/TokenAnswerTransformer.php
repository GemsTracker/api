<?php

namespace Pulse\Api\Model\Transformer;

class TokenAnswerTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var int
     */
    protected $currentUserId;

    protected $language;

    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    public function __construct(\Gems_Tracker $tracker, $language, $currentUserId)
    {
        $this->tracker = $tracker;
        $this->language = $language;
        $this->currentUserId = $currentUserId;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        $tokensBySource = [];
        $sourceSurveyIds = [];
        foreach($data as $row) {
            $tokensBySource[$row['gsu_id_source']][$row['gto_id_survey']][] = $row['gto_id_token'];
            $sourceSurveyIds[$row['gto_id_survey']] = $row['gsu_surveyor_id'];
        }

        $tokenItems = [];

        foreach ($tokensBySource as $sourceId => $tokensBySurvey) {
            $source = $this->tracker->getSource($sourceId);
            foreach ($tokensBySurvey as $surveyId => $tokenIds) {
                $tokenAnswerRows = $source->getRawTokenAnswerRows(['token' => $tokenIds], $surveyId, $sourceSurveyIds[$surveyId]);
                $surveyInformation = $source->getQuestionInformation($this->language, $surveyId, $sourceSurveyIds[$surveyId]);

                foreach($tokenAnswerRows as $tokenRow) {
                    $tokenItems[$tokenRow['token']] = array_intersect_key($tokenRow, $surveyInformation);
                }
            }
        }

        foreach ($data as $key => $row) {
            $tokenId = str_replace('-', '_', $row['gto_id_token']);
            $answers = null;
            if (isset($tokenItems[$tokenId])) {
                $answers = array_filter($tokenItems[$tokenId]);
                if (empty($answers)) {
                    $answers = null;
                }
            }
            $data[$key]['answers'] = $answers;
        }

        return $data;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (isset($row['answers'])) {
            $token = $this->tracker->getToken($row['gto_id_token']);
            if ($token->isCompleted()) {
                throw new \Exception('Token is already completed');
            }
            if (!$token->isCurrentlyValid()) {
                throw new \Exception('Token is currently not valid');
            }
            $questionInformation = $token->getSurvey()->getQuestionInformation($this->language);

            $answers = [];
            foreach($row['answers'] as $questionCode => $answer) {
                $answer = htmlspecialchars($answer);
                if (isset($questionInformation[$questionCode])) {
                    if (isset($questionInformation[$questionCode]['answers']) && is_array($questionInformation[$questionCode]['answers']) && !isset($questionInformation[$questionCode]['answers'][$answer])) {
                        throw new \Exception(sprintf('Answer %s is not a valid answer for question %s', $answer, $questionCode));
                    }
                    $answers[$questionCode] = $answer;
                }
            }

            if (!$token->inSource()) {
                $token->getUrl($this->language, $token->getRespondentId());
            }

            $token->setRawAnswers($answers);

            $token->setCompletionTime(new \MUtil_Date(), $token->getRespondentId());
            if ($token instanceof \Pulse_Tracker_Token) {
                $token->setComment('Answered in digital-clinic', $token->getRespondentId());
                $token->setBy($token->getRespondentId());
            }

            $this->tracker->processCompletedTokens($token->getRespondentId(), $this->currentUserId);
        }

        return $row;
    }
}