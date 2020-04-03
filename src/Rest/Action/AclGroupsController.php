<?php

namespace Gems\Rest\Action;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;

class AclGroupsController extends RestControllerAbstract
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function get()
    {
        if (array_key_exists('acl-groups', $this->config)) {
            return new JsonResponse($this->config['acl-groups']);
        }
        return new EmptyResponse();
    }
}
