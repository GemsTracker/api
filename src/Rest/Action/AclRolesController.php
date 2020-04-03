<?php


namespace Gems\Rest\Action;


use Gems\Rest\Acl\AclRepository;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;

class AclRolesController extends RestControllerAbstract
{
    /**
     * @var AclRepository
     */
    protected $aclRepository;

    public function __construct(AclRepository $aclRepository)
    {
        $this->aclRepository = $aclRepository;
    }

    public function get()
    {
        $roles = $this->aclRepository->getRoles();
        if (!empty($roles)) {
            return new JsonResponse($roles);
        }
        return new EmptyResponse();
    }
}
