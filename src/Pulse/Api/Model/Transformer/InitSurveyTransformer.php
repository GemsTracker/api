<?php

namespace Pulse\Api\Model\Transformer;

class InitSurveyTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    private $tracker;
    private $language;
    private $currentUserId;

    public function __construct(\Gems_Tracker $tracker, $language, $currentUserId)
    {
        $this->tracker = $tracker;
        $this->language = $language;
        $this->currentUserId = $currentUserId;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        if (count($data) === 1) {
            // single token mode!
            $currentTokenData = reset($data);
            $token = $this->tracker->getToken($currentTokenData);

            if (!$token->isCompleted() && $token->isCurrentlyValid()) {
                $token->setTokenStart($this->language, $this->currentUserId);
                $token->handleBeforeAnswering();
                $token->getRawAnswers();
            }
        }
    }
}