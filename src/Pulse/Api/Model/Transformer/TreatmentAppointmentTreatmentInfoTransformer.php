<?php

namespace Pulse\Api\Model\Transformer;

use Ichom\Repository\Diagnosis2TreatmentRepository;

class TreatmentAppointmentTreatmentInfoTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    private \Zend_Translate_Adapter $translator;

    public function __construct(
        \Zend_Translate_Adapter $translator
    )
    {
        $this->translator = $translator;
    }

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['with-treatment'])) {
            if ($filter['with-treatment'] == 1) {
                if ($model instanceof \MUtil_Model_DatabaseModelAbstract) {
                    $model->addColumn(new \Zend_Db_Expr('pa2t_id_treatment'), 'treatment');
                }
            }
            unset($filter['with-treatment']);
        }

        return $filter;
    }
}