<?php


namespace Gems\Rest\Action;


use Gems\Rest\Acl\AclRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class AclRolePermissionsController extends RestControllerAbstract
{
    /**
     * @var Adapter
     */
    protected $db;
    /**
     * @var AclRepository
     */
    private $aclRepository;

    public function __construct(AclRepository $aclRepository, Adapter $db)
    {
        $this->aclRepository = $aclRepository;
        $this->db = $db;
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $role = $request->getAttribute('role');

        $permissions = $this->aclRepository->getExistingPermissions($role);
        if (!empty($permissions)) {
            return new JsonResponse($permissions);
        }
        return new EmptyResponse();
    }

    public function patch(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $role = $request->getAttribute('role');
        $updatedPermissions = json_decode($request->getBody()->getContents(), true);
        $allPermissions = $this->aclRepository->getRoutePermissions();
        $existingPermissions = $this->aclRepository->getExistingPermissions($role);

        $deletePermissions = [];
        //$updatePermissions = [];
        $createPermissions = [];

        // Cleanup
        foreach($existingPermissions as $permission=>$methods) {
            if (!array_key_exists($permission, $allPermissions)) {
                $deletePermissions[$permission] = $methods;
                continue;
            }
            foreach($methods as $method) {
                if (!in_array($method, $allPermissions[$permission])) {
                    if (!isset($deletePermissions[$permission])) {
                        $deletePermissions[$permission] = [];
                    }
                    if (!in_array($method, $deletePermissions[$permission])) {
                        $deletePermissions[$permission][] = $method;
                    }
                }
            }
        }

        foreach($updatedPermissions as $permission=>$methods) {
            if (!array_key_exists($permission, $allPermissions)) {
                /*if (array_key_exists($permission, $existingPermissions)) {
                    $deletePermissions[$permission] = array_keys($methods);
                }*/
                continue;
            }

            /*if (!array_key_exists($permission, $existingPermissions)) {

                $createPermissions[$permission] = array_keys($methods);
                continue;
            }*/

            foreach($methods as $method=>$value) {
                if (!in_array($method, $allPermissions[$permission])) {
                    continue;
                }
                if ($value === true) {
                    if (!array_key_exists($permission, $existingPermissions) || !in_array($method, $existingPermissions[$permission])) {
                        if (!isset($createPermissions[$permission])) {
                            $createPermissions[$permission] = [];
                        }
                        if (!in_array($method, $createPermissions[$permission])) {
                            $createPermissions[$permission][] = $method;
                        }
                    } else {
                        continue;
                    }
                } else {
                    if (array_key_exists($permission, $existingPermissions) && in_array($method, $existingPermissions[$permission])) {
                        if (!isset($deletePermissions[$permission])) {
                            $deletePermissions[$permission] = [];
                        }
                        if (!in_array($method, $deletePermissions[$permission])) {
                            $deletePermissions[$permission][] = $method;
                        }
                    }
                }
            }
        }

        if (!empty($createPermissions)) {
            foreach($createPermissions as $permission=>$methods) {
                foreach($methods as $key=>$method) {
                    $this->aclRepository->createPermission($role, $permission, $method);
                }
            }
        }
        if (!empty($deletePermissions)) {
            foreach($deletePermissions as $permission=>$methods) {
                foreach($methods as $key=>$method) {
                    $this->aclRepository->removePermission($role, $permission, $method);
                }
            }
        }

        return new EmptyResponse(201);
    }
}