<?php

declare(strict_types=1);


namespace Pulse\Api\Repository;


use Psr\Http\Message\ServerRequestInterface;

class RequestRepository
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * Get the user IP address
     *
     * @return string|null
     */
    public function getIp()
    {
        $params = $this->request->getServerParams();
        if (isset($params['REMOTE_ADDR'])) {
            return $params['REMOTE_ADDR'];
        }
        return null;
    }

    public function getMethod()
    {
        return $this->request->getMethod();
    }

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
}
