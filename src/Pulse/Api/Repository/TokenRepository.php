<?php


namespace Pulse\Api\Repository;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;

class TokenRepository
{
    /**
     * @var Adapter
     */
    protected $db;

    /**
     * @var SelectTranslator
     */
    protected $selectTranslator;

    public function __construct(Adapter $db, SelectTranslator $selectTranslator)
    {
        $this->db = $db;
        $this->selectTranslator = $selectTranslator;
    }

    public function getLatestTokensForSurveyCodes($params)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from(['r1' => 'gems__respondent2org'])
            ->join(['t1' => 'gems__tokens'], 'r1.gr2o_id_organization = t1.gto_id_organization AND r1.gr2o_id_user = t1.gto_id_respondent')
            ->join(['s1' => 'gems__surveys'], 't1.gto_id_survey = s1.gsu_id_survey')
            ->join(['rc1' => 'gems__reception_codes'], 't1.gto_reception_code = rc1.grc_id_reception_code')
            ->join(['t2' => 'gems__tokens'], 'r1.gr2o_id_organization = t2.gto_id_organization AND r1.gr2o_id_user = t2.gto_id_respondent AND t1.gto_id_survey = t2.gto_id_survey AND t1.gto_completion_time < t2.gto_completion_time', [], Select::JOIN_LEFT)
            ->where->isNull('t2.gto_id_token');

        $prefixedParams = [];
        foreach($params as $name=>$value) {
            if (strpos($name, 'gto_') === 0) {
                $newName = 't1.'.$name;
                $prefixedParams[$newName] = $value;
            } else {
                $prefixedParams[$name] = $value;
            }
        }

        $select = $this->selectTranslator->addRequestParamsToSelect($select, $prefixedParams);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        $rows = iterator_to_array($result);

        if ($rows) {
            return $rows;
        }

        return false;
    }

}
