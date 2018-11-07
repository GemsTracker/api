<?php


namespace Pulse\Api\Action;


use Gems\Rest\Acl\AclRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Permissions\Acl\Acl;

class PermissionGeneratorController implements MiddlewareInterface
{
    /**
     * @var Acl
     */
    protected $acl;

    /**
     * @var AclRepository
     */
    protected $aclRepository;

    /**
     * @var Adapter
     */
    protected $db;

    public function __construct(AclRepository $aclRepository, Acl $acl, Adapter $db)
    {
        $this->aclRepository = $aclRepository;
        $this->acl = $acl;
        $this->db = $db;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $queryParams = $request->getQueryParams();
        $currentRole = $this->getCurrentRole($queryParams);
        $requiredPermissions = $this->getRequiredPermissions($queryParams);


        $existingPermissions = $this->getExistingPermissions($currentRole);
        if (isset($queryParams['all']) && $queryParams['all'] == 1) {
            $existingPermissions = [];
        }

        $this->getPermissionQueries($requiredPermissions, $existingPermissions, $currentRole);

        /*echo 'super: api.organizations2.get:' . (int) $this->acl->isAllowed('super', 'api.organizations2', 'GET') . '<br>';
        echo 'admin: api.organizations2.get:' . (int) $this->acl->isAllowed('admin', 'api.organizations2', 'GET') . '<br>';*/

        return new HtmlResponse('', 200);
    }

    protected function getCurrentRole($queryParams)
    {
        $currentRole = 'super';
        $roles = array_flip($this->aclRepository->getRoles());

        if (isset($queryParams['role'], $roles[$queryParams['role']])) {
            $currentRole = $queryParams['role'];
        }

        echo "<h1>$currentRole</h1>";

        return $currentRole;
    }

    protected function getExistingPermissions($currentRole)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();

        $select->from('gems__api_permissions')
            ->where(['gapr_allowed' => 1, 'gapr_role' => $currentRole]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        $permissions = iterator_to_array($result);

        $groupedPermissions = [];
        foreach($permissions as $permission) {
            if (!array_key_exists($permission['gapr_resource'], $groupedPermissions)) {
                $groupedPermissions[$permission['gapr_resource']] = [];
            }
            $groupedPermissions[$permission['gapr_resource']][] = $permission['gapr_permission'];
        }

        return $groupedPermissions;
    }

    protected function getPermissionQueries($requiredPermissions, $existingPermissions, $currentRole)
    {
        $skipResources = [];
        $skipPermissions = [];

        $unneededPermissions = [];

        foreach($requiredPermissions as $resource=>$permissions) {

            $originalPermissions = array_flip($permissions);
            if (array_key_exists($resource, $existingPermissions)) {
                $permissions = array_diff($permissions, $existingPermissions[$resource]);
                $unneededPermissions[$resource] = array_diff($existingPermissions[$resource], $permissions);
            }

            if (empty($permissions)) {
                $skipResources[] = $resource;
                continue;
            }

            $sql =  'INSERT INTO gems__api_permissions (gapr_role, gapr_resource, gapr_permission, gapr_allowed) 
                VALUES ';

            $values = [];
            foreach($permissions as $permission) {
                $values[] = "('{$currentRole}', '{$resource}', '{$permission}', 1)";
                unset($originalPermissions[$permission]);
            }

            if (!empty($originalPermissions)) {
                $skipPermissions[$resource] = array_flip($originalPermissions);
            }

            $sql .= join(', ', $values) . ';';

            echo $sql . '<br><br>';

            unset($existingPermissions[$resource]);
        }

        foreach($skipResources as $resource) {
            echo "Skipped adding '{$resource}' because it already exists in the database.<br>";
        }

        foreach($skipPermissions as $resource => $permissions) {
            echo "Skipped adding permissions '" . join(',', $permissions) . "' for resource '{$resource}' because it already exists in the database.<br>";
        }
    }

    protected function getRequiredPermissions($queryParams)
    {
        $aclGroupsConfig = include(__DIR__ . '/../Acl/AclGroupsConfig.php');
        $requiredPermissions = $globalPermissions = $this->aclRepository->getRoutePermissions();

        $usingGroup = 'global (all routes!)';
        if (isset($queryParams['group'], $aclGroupsConfig[$queryParams['group']])) {
            $requiredPermissions = $aclGroupsConfig[$queryParams['group']];
            $usingGroup = $queryParams['group'];

            foreach ($requiredPermissions as $resource => $permissions) {
                if (!array_key_exists($resource, $globalPermissions)) {
                    echo "Warning: Group resource {$resource} does not exist as route name";
                }
            }
        }

        echo "<h2>Group: {$usingGroup}</h2>";

        if (isset($queryParams['resource'], $requiredPermissions[$queryParams['resource']])) {
            echo "<h2>For resource: {$queryParams['resource']}</h2>";
            $selectedPermission = [
                $queryParams['resource'] => $requiredPermissions[$queryParams['resource']]
            ];
            $requiredPermissions = $selectedPermission;
        }

        return $requiredPermissions;
    }
}