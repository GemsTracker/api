<?php

namespace Gems\Rest\Action;

class OrganizationController extends ModelRestControllerAbstract
{
    protected $itemsPerPage = 5;

    protected function createModel()
    {
        return $this->loader->create('Model_OrganizationModel');
    }
}