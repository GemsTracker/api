<?php


namespace Gems\Rest\Acl;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Sql;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\GenericResource;
use Laminas\Permissions\Acl\Role\GenericRole;

class AclRepository
{
    /**
     * @var array List of Permissions grouped by roles and resources
     */
    protected $apiPermissions;

    /**
     * @var array with configuration values
     */
    protected $config;

    /**
     * @var Adapter
     */
    protected $db;

    /**
     * @var array List of Roles from Gemstracker;
     */
    protected $gemsRoles;

    public function __construct(Adapter $db, $config)
    {
        $this->db = $db;
        $this->config = $config;

        $this->acl = new Acl();
    }

    /**
     * Add a role and it's subroles
     *
     * @param $role array Database row of role you want to add
     * @param $roles array list of role ID's and their values from DB
     */
    public function addRole($role, $roles)
    {
        // Could have been added as parent
        if ($this->acl->hasRole($role['grl_name'])) {
            return;
        }

        // Parents should be added first
        $parentNames = null;
        if (is_array($role['grl_parents'])) {
            $parentNames = [];
            foreach($role['grl_parents'] as $parentRoleId) {
                if (!$this->acl->hasRole($roles[$parentRoleId]['grl_name'])) {
                    $this->addRole($roles[$parentRoleId], $roles);
                }
                $parentNames[] = $roles[$parentRoleId]['grl_name'];
            }
        }

        $this->acl->addRole(new GenericRole($role['grl_name']), $parentNames);

        return;
    }

    public function createPermission($role, $permission, $method)
    {
        $sql = new Sql($this->db);
        $insert = $sql->insert();
        $insert->into('gems__api_permissions')
            ->columns(['gapr_role', 'gapr_resource', 'gapr_permission', 'gapr_allowed'])
            ->values(
                [
                    'gapr_role'         => $role,
                    'gapr_resource'     => $permission,
                    'gapr_permission'   => $method,
                    'gapr_allowed'      => 1,
                ]
            );

        try {
            $statement = $sql->prepareStatementForSqlObject($insert);
            $statement->execute();
        } catch(\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Change result to array with keyfield as key
     *
     * @param ResultInterface $result result from query
     * @param bool $keyField the field in the result that should be used as key.
     *  If not provided, it'll be a normal numbered array
     * @return array
     */
    protected function flattenRowResult(ResultInterface $result, $keyField=false)
    {
        $resultArray = iterator_to_array($result);

        if ($keyField === false) {
            return array_values($resultArray);
        }

        $sortedArray = [];
        foreach($resultArray as $value) {
            if (isset($value)) {
                $sortedArray[$value[$keyField]] = $value;
            }
        }


        return $sortedArray;
    }


    /**
     * Get the Zend\Permissions\Acl\Acl object with loaded values from DB
     *
     * @return Acl
     */
    public function getAcl()
    {
        $this->loadAcl();
        return $this->acl;
    }

    protected function getApiPermissions()
    {
        if (!$this->apiPermissions) {
            $sql = new Sql($this->db);
            $select = $sql->select();

            $select->from('gems__api_permissions');

            $statement = $sql->prepareStatementForSqlObject($select);
            $result = $statement->execute();

            $resultArray = iterator_to_array($result);

            $groupedPermissions = [];
            foreach($resultArray as $permission) {
                if ($permission['gapr_allowed'] == 1) {
                    $groupedPermissions[$permission['gapr_role']][$permission['gapr_resource']][] = $permission['gapr_permission'];
                }
            }

            $this->apiPermissions = $groupedPermissions;
        }

        return $this->apiPermissions;
    }

    /**
     * Get the existing roles from a specific role
     *
     * @param string $currentRole
     * @return array
     */
    public function getExistingPermissions($currentRole)
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

    /**
     * Get list of Roles from the DB
     *
     * @return array
     */
    protected function getGemsRoles()
    {
        if (!$this->gemsRoles) {
            $sql = new Sql($this->db);
            $select = $sql->select();

            $select->from('gems__roles')
                ->columns(['grl_id_role', 'grl_name', 'grl_parents', 'grl_privileges']);

            $statement = $sql->prepareStatementForSqlObject($select);
            $result = $statement->execute();

            if ($result instanceof ResultInterface && $result->isQueryResult()) {
                $roles = $this->flattenRowResult($result, 'grl_id_role');
                $roles = $this->translateRoleParents($roles);
            } else {
                throw \Exception('Could not retrieve roles');
            }

            $this->gemsRoles = $roles;
        }


        return $this->gemsRoles;
    }

    /**
     * Get the current roles in the Acl
     *
     * @return array
     */
    public function getRoles()
    {
        return $this->acl->getRoles();
    }

    /**
     * * Get all permissions based on the routes defined in the config
     *
     * @return array
     * @throws \Exception
     */
    public function getRoutePermissions()
    {
        $permissions = [];
        foreach($this->config['routes'] as $route) {
            if (!isset($route['name'])) {
                throw new \Exception('Name not set in route');
            }


            $baseRoute = str_replace(['.structure', '.get', '.fixed'], '', $route['name']);
            $methods = ['all'];

            if (isset($route['allowed_methods'])) {
                $methods = $route['allowed_methods'];
            }
            /*if (isset($route['options'], $route['options']['methods'])) {
                $methods = $route['options']['methods'];
            }*/
            if (isset($permissions[$baseRoute])) {
                $permissions[$baseRoute] = array_unique(array_merge($permissions[$baseRoute], $methods));
            } else {
                $permissions[$baseRoute] = $methods;
            }
        }
        return $permissions;
    }

    /**
     * Load Roles and permissions from Acl
     */
    public function loadAcl()
    {
        $this->loadRoles();
        $this->loadPermissions();
    }

    protected function loadMasterPermissions()
    {
        $routePermissions = $this->getRoutePermissions();
        $this->acl->addRole(new GenericRole('master'));
        foreach($routePermissions as $resource=>$permissions) {
            if (!$this->acl->hasResource($resource)) {
                $this->acl->addResource(new GenericResource($resource));
            }
            $this->acl->allow('master', $resource, $permissions);
        }
    }

    /**
     * Load the permissions from the Gems Database
     */
    protected function loadPermissions()
    {
        $roles = $this->getGemsRoles();
        foreach ($roles as $role) {
            $permissions = explode(',', $role['grl_privileges']);
            $this->acl->allow($role['grl_name'], null, $permissions);
        }

        $apiPermissions = $this->getApiPermissions();
        foreach($apiPermissions as $role=>$resources) {
            foreach($resources as $resource=>$permissions) {
                if (!$this->acl->hasResource($resource)) {
                    $this->acl->addResource(new GenericResource($resource));
                }
                $this->acl->allow($role, $resource, $permissions);
            }
        }

        $this->loadMasterPermissions();

    }

    /**
     * Load the roles from the Gemstracker database
     */
    protected function loadRoles()
    {
        $roles = $this->getGemsRoles();
        foreach($roles as $role) {
            $this->addRole($role, $roles);
        }
    }

    public function removePermission($role, $permission, $method)
    {
        $sql = new Sql($this->db);
        $delete = $sql->delete();
        $delete->from('gems__api_permissions')
            ->where([
                'gapr_role'         => $role,
                'gapr_resource'     => $permission,
                'gapr_permission'   => $method,
                'gapr_allowed'      => 1,
            ]);

        try {
            $statement = $sql->prepareStatementForSqlObject($delete);
            $statement->execute();
        } catch(\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * translate role parents to array values or null
     *
     * @param $rows
     * @return array
     */
    protected function translateRoleParents($roles)
    {
        foreach($roles as $roleId=>$role) {

            $parents = false;
            if (!empty($role['grl_parents'])) {
                $parents = explode(',', $role['grl_parents']);
            }

            if ($parents === false || count($parents) === 0) {
                $roles[$roleId]['grl_parents'] = null;
            } else {
                $roles[$roleId]['grl_parents'] = $parents;
            }

        }

        return $roles;
    }
}
