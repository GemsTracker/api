<?php


namespace Gems\Rest\Action;

use Gems\Rest\Acl\AclRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Permissions\Acl\Acl;

class DevAction implements MiddlewareInterface
{
    protected $aclRepository;

    public function __construct(AclRepository $aclRepository, Acl $acl)
    {
        $this->aclRepository = $aclRepository;
        $this->acl = $acl;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $roles = $this->aclRepository->getRoles();

        foreach($roles as $roleName) {
            echo $roleName . ': ' . (int)$this->acl->isAllowed($roleName, null, 'pr.pulse.treatement2track.edit') . '<br>';
        }

        echo '<hr>';

        foreach($this->aclRepository->getRoutePermissions() as $resource=>$permissions) {

            $sql =  'INSERT INTO gems__api_permissions (gapr_role, gapr_resource, gapr_permission, gapr_allowed) 
                VALUES ';

            $values = [];
            foreach($permissions as $permission) {
                $values[] = "('super', '{$resource}', '{$permission}', 1)";
            }

            $sql .= join(', ', $values) . ';';

            echo $sql . '<br><br>';
        }

        echo '<hr>';

        echo 'super: api.organizations2.get:' . (int) $this->acl->isAllowed('super', 'api.organizations2', 'GET') . '<br>';
        echo 'admin: api.organizations2.get:' . (int) $this->acl->isAllowed('admin', 'api.organizations2', 'GET') . '<br>';

        return new HtmlResponse('Hello', 200);
    }
}
