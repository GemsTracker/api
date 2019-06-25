<?php

/* Return one array with as keys the group name and the route names and their allowed methods as values
e.g.: 'test-group' => [
    'api.test' => ['GET'],
    'other-test' => ['GET', 'PATCH', 'POST', 'DELETE'],
];
*/

return [
    'api-permissions' => [
        'acl-groups' => ['GET'],
        'acl-global-permissions' => ['GET'],
        'acl-role-permissions' => ['GET', 'PATCH'],
        'acl-roles' => ['GET'],
        'api-roles' => ['GET'],
    ],
];