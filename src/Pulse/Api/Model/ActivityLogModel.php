<?php

declare(strict_types=1);


namespace Pulse\Api\Model;


use MUtil\Model\Type\JsonData;
use MUtil\Translate\TranslateableTrait;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Model\Transformer\ActivityLogActionTransformer;
use Pulse\Api\Model\Transformer\ActivityLogRequestInfoTransformer;
use Pulse\Api\Model\Transformer\ActivityLogRespondentTransformer;
use Pulse\Api\Model\Transformer\ActivityLogUserTransformer;
use Pulse\Api\Repository\ActivityActionRepository;
use Pulse\Api\Repository\RequestRepository;
use Pulse\Api\Repository\RespondentRepository;
use Pulse\Model\ModelUpdateDiffs;

class ActivityLogModel extends \MUtil_Model_JoinModel
{
    use TranslateableTrait;
    use ModelUpdateDiffs;

    public function __construct(ActivityActionRepository $activityActionRepository, CurrentUserRepository $currentUserRepository, RequestRepository $requestRepository, RespondentRepository $respondentRepository)
    {
        parent::__construct('activityLog', 'gems__log_activity', true);
        $this->addTable('gems__log_actions', ['glac_id_action' => 'gla_action'], false);
        $this->addTransformer(new ActivityLogActionTransformer($activityActionRepository));
        $this->addTransformer(new ActivityLogUserTransformer($currentUserRepository));
        $this->addTransformer(new ActivityLogRespondentTransformer($respondentRepository));
        $this->addTransformer(new ActivityLogRequestInfoTransformer($requestRepository));
    }

    public function afterRegistry()
    {
        $this->set('gla_id', [
            'label' => $this->_('ID'),
            'apiName' => 'id',
        ]);

        $this->set('actionName', [
            'label' => $this->_('Action name'),
            'apiName' => 'action',
            'required' => true,
        ]);

        $this->set('gla_respondent_id', [
            'label' => $this->_('Respondent'),
            'apiName' => 'respondentId',
        ]);

        $this->set('gla_by', [
            'label' => $this->_('User'),
            'apiName' => 'userId',
        ]);

        $this->set('gla_organization', [
            'label' => $this->_('Organization'),
            'apiName' => 'organizationId',
            'required' => true,
        ]);

        $this->set('gla_role', [
            'label' => $this->_('Role'),
            'apiName' => 'role',
        ]);

        $this->set('gla_changed', [
            'label' => $this->_('Data changed'),
            'apiName' => 'dataChanged',
        ]);

        $this->set('gla_message', [
            'label' => $this->_('Message'),
            'apiName' => 'message',
        ]);

        $jdType = new JsonData();
        $jdType->apply($this, 'gla_message', true);

        $this->set('gla_data', [
            'label' => $this->_('Data'),
            'apiName' => 'data',
        ]);
        $jdType->apply($this, 'gla_data', true);

        $this->set('gla_method', [
            'label' => $this->_('Method'),
            'apiName' => 'method',
        ]);

        $this->set('gla_remote_ip', [
            'label' => $this->_('Remote IP'),
            'apiName' => 'remoteIp',
        ]);

        $this->set('gla_created', [
            'label' => $this->_('Created'),
            'apiName' => 'created',
        ]);
    }
}
