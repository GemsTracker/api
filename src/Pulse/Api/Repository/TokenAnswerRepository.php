<?php


namespace Pulse\Api\Repository;


class TokenAnswerRepository
{
    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    public function __construct(\Gems_Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function getTokenAnswers($tokenId)
    {
        $token = $this->tracker->getToken($tokenId);
        return $token->getRawAnswers();
    }
}