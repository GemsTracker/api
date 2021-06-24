<?php

namespace Gems\ReferenceData\Model;


use Gems\Rest\Fhir\Model\Transformer\ManagingOrganizationTransformer;

class ReferenceCollectionModelWithOrganization extends ReferenceCollectionModel
{
    protected function addTransformers()
    {
        $this->addTransformer(new ManagingOrganizationTransformer('grfd_id_organization', true, 'organization'));
        parent::addTransformers();
    }
}
