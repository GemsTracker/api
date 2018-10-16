<?php


namespace Pulse\Api\Model;


class DiagnosisModel extends \MUtil_Model_JoinModel
{
    public function __construct()
    {
        parent::__construct('agenda_diagnoses', 'gems__agenda_diagnoses', true);
        \Gems_Model::setChangeFieldsByPrefix($this, 'gad');
    }
}