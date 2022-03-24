<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Repository;


use Psr\Http\Message\ServerRequestInterface;

class CurrentUserRepository
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * Set the psr-7 Request
     *
     * @param ServerRequestInterface $request
     * @return void
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Get the user ID from the request attribute
     *
     * @return int user ID
     */
    public function getUserId()
    {
        return $this->request->getAttribute('user_id');
    }

    /**
     * Get the username from the request attribute
     *
     * @return string username
     */
    public function getUserName()
    {
        return $this->request->getAttribute('user_name');
    }
}
