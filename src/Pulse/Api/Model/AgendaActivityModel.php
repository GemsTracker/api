<?php

declare(strict_types=1);


namespace Pulse\Api\Model;


use MUtil\Translate\TranslateableTrait;

class AgendaActivityModel extends \MUtil_Model_TableModel
{
    use TranslateableTrait;

    public function __construct()
    {
        parent::__construct('gems__agenda_activities', 'agendaActivityModel');
        \Gems_Model::setChangeFieldsByPrefix($this, 'gaa');
    }

    public function afterRegistry()
    {
        $this->set('gaa_id_activity', [
            'label' => $this->_('ID'),
            'apiName' => 'id',
        ]);

        $this->set('gaa_name', [
            'label' => $this->_('Name'),
            'apiName' => 'name',
        ]);

        $this->set('gaa_id_organization', [
            'label' => $this->_('Organization'),
            'apiName' => 'organization',
        ]);

        $this->set('gaa_active', [
            'label' => $this->_('Active'),
            'apiName' => 'active',
        ]);


    }
}
