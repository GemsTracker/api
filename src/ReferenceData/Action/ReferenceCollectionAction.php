<?php

namespace Gems\ReferenceData\Action;


use Gems\Rest\Action\ModelRestController;
use Psr\Http\Message\ServerRequestInterface;

class ReferenceCollectionAction extends ModelRestController
{

    /**
     * Get the allowed filter fields, null if all is allowed
     *
     * @return null|string[]
     */
    protected function getAllowedFilterFields()
    {
        return null;
    }

    /**
     * Get pagination filters for listing model items with a GET request
     *
     * uses per_page and page to set the sql limit
     *
     * @param ServerRequestInterface $request
     * @param $filters
     * @return mixed
     */
    public function getListPagination(ServerRequestInterface $request, $filters)
    {
        return $filters;
    }
}
