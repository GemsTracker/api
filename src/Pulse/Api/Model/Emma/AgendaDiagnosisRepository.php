<?php


namespace Pulse\Api\Model\Emma;


use Pulse\Api\Model\DiagnosisModel;
use Zalt\Loader\ProjectOverloader;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class AgendaDiagnosisRepository
{
    /**
     * @var Adapter
     */
    protected $db;

    protected $matchedDiagnoses;

    /**
     * @var \MUtil_Model_ModelAbstract;
     */
    protected $model;

    public function __construct(Adapter $db, ProjectOverloader $loader)
    {
        $this->db = $db;
        $this->loader = $loader;
    }

    public function addDiagnosis($sourceId, $source, $description=null)
    {
        $newValues = [
            'gad_diagnosis_code' => $sourceId,
            'gad_source' => $source,
            'gad_description' => $description,
        ];

        $model = $this->getModel();
        $newValues = $model->save($newValues);
        if (isset($newValues['gad_diagnosis_code'])) {
            return $newValues['gad_diagnosis_code'];
        }
        return false;
    }

    protected function createModel()
    {
        $model = $this->loader->create(DiagnosisModel::class);
        return $model;
    }

    protected function getModel()
    {
        if (!$this->model instanceof \MUtil_Model_ModelAbstract) {
            $this->model = $this->createModel();
        }

        return $this->model;
    }

    public function matchDiagnosis($code, $source, $description=null)
    {
        if ($diagnosis = $this->findDiagnosis($code, $source)) {
            return $diagnosis['gad_diagnosis_code'];
        }

        $diagnosisCode = $this->addDiagnosis($code, $source, $description);
        return $diagnosisCode;
    }

    public function findDiagnosis($code, $source)
    {
        if (isset($this->matchedDiagnoses[$source], $this->matchedDiagnoses[$source][$code])) {
            return $this->matchedDiagnoses[$source][$code];
        }

        $model = $this->getModel();

        $result = $model->loadFirst(
            [
                'gad_diagnosis_code' => $code,
                'gad_source' => $source,
                'gad_active' => 1,
            ]
        );

        if ($result) {
            $this->matchedDiagnoses[$source][$code] = $result;
            return $result;
        }

        /*$sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__agenda_diagnoses')
            ->columns(['gad_diagnosis_code'])
            ->where(
                [
                    'gad_id_in_source' => $sourceId,
                    'gad_source' => $source,
                    'gad_active' => 1,
                ]
            );

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        if ($result->count() > 0) {
            $diagnosis = $result->current();
            $this->matchedDiagnoses[$source][$sourceId] = $diagnosis;
            return $diagnosis;
        }*/

        return false;
    }
}