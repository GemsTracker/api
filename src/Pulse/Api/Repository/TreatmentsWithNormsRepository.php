<?php


namespace Pulse\Api\Repository;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

class TreatmentsWithNormsRepository
{
    public function __construct(Adapter $db, RespondentResults $respondentResults)
    {
        $this->db = $db;
        $this->respondentResults = $respondentResults;
    }

    public function getTreatments($params)
    {

        $treatmentField = $this->respondentResults->getDbField('treatment');

        $sql = new Sql($this->db);
        $select = $sql->select();

        $select
            ->from('pulse__treatments')
            ->columns(['ptr_id_treatment', 'ptr_name'])
            ->join('pulse__treatment2outcomevariable', 'ptr_id_treatment = pt2o_id_treatment', [])
            ->join('gems__norms','gno_survey_id = pt2o_id_survey AND gno_answer_code = pt2o_question_code AND ' . $treatmentField . ' = pt2o_id_treatment', [])
            ->where([
                'ptr_active' => 1,
                'pt2o_active' => 1,
            ])
            ->group('pt2o_id_treatment')
            ->order('ptr_name')
            ->where->isNull('ptr_duplicate_of');

        if (isset($params['ptr_id_organization'])) {
            $select->where(['ptr_id_organization' => $params['ptr_id_organization']]);
        }

        $test = $select->getSqlString($this->db->getPlatform());

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        $treatments = iterator_to_array($result);

        //print_r($treatments);
        //die;

        return $treatments;
    }
}
