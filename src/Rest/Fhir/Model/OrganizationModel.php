<?php


namespace Gems\Rest\Fhir\Model;


use Gems\Rest\Fhir\Model\Transformer\BooleanTransformer;
use Gems\Rest\Fhir\Model\Transformer\OrganizationContactTransformer;
use Gems\Rest\Fhir\Model\Transformer\OrganizationTelecomTransformer;

class OrganizationModel extends \Gems_Model_OrganizationModel
{
    public function __construct()
    {
        parent::__construct([]);

        $this->set('gor_id_organization', 'label', $this->_('id'), 'apiName', 'id');
        $this->set('gor_active', 'label', $this->_('active'), 'apiName', 'active');
        $this->set('gor_name', 'label', $this->_('name'), 'apiName', 'name');
        $this->set('telecom', 'label', $this->_('telecom'));
        $this->set('contact', 'label', $this->_('contact'));
        $this->set('gor_code', 'label', $this->_('code'), 'apiName', 'code');

        $this->addTransformer(new OrganizationTelecomTransformer());
        $this->addTransformer(new OrganizationContactTransformer());
        $this->addTransformer(new BooleanTransformer(['gor_active']));
    }
}
