<?php


namespace Gems\Rest\Fhir\Model;


class ServiceTypeModel extends \Gems_Model_JoinModel
{
    public function __construct()
    {
        parent::__construct('serviceType', 'gems__agenda_activities', 'gaa', false);

        $this->set('gaa_id_activity', 'label', $this->_('code'), 'apiName', 'code');
        $this->set('gaa_name', 'label', $this->_('display'), 'apiName', 'display');

    }
}
