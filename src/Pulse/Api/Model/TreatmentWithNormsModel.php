<?php

namespace Pulse\Api\Model;

class TreatmentWithNormsModel extends \Gems_Model_JoinModel
{
    public function __construct()
    {
        parent::__construct('treatment-with-norms','gems__treatments', 'gtrt', false);
        $this->addTable('pulse__treatment2outcomevariable',
            ['gtrt_id_treatment' => 'pt2o_id_treatment']
        );
        $this->addTable('gems__norms',
            [
                'gno_survey_id' => 'pt2o_id_survey',
                'gno_answer_code' => 'pt2o_question_code'
            ]
        );
    }
}
