<?php


namespace Gems\Rest\Action;


use Gems\Rest\Acl\AclRepository;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class AclGlobalPermissionsController extends RestControllerAbstract
{
    /**
     * @var AclRepository
     */
    protected $aclRepository;

    public function __construct(AclRepository $aclRepository, $config)
    {
        $this->aclRepository    = $aclRepository;
        $this->config           = $config;
    }

    public function get()
    {
        $routePermissions = $this->aclRepository->getRoutePermissions();
        if (!empty($routePermissions)) {
            return new JsonResponse($routePermissions);
        }
        return new EmptyResponse();
    }
}