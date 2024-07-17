<?php

namespace Pulse\Api\Model\Transformer;

use Gems\Rest\Db\ResultFetcher;

class TreatmentAppointmentSedationInfoTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $loadSedation = false;

    /**
     * @var array|null
     */
    protected $sedations = null;
    private ResultFetcher $resultFetcher;

    public function __construct(
        ResultFetcher $resultFetcher

    )
    {
        $this->resultFetcher = $resultFetcher;
    }

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['with-sedation'])) {
            if ($filter['with-sedation'] == 1) {
                $this->loadSedation = true;
            }
            unset($filter['with-sedation']);
        }

        return $filter;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        if (!$this->loadSedation) {
            return $data;
        }

        foreach($data as $key => $row) {
            $data[$key]['sedation'] = $this->getSedationFromAppointmentActivity($row['gaa_name']);

            /*$data[$key]['sedation'] = [
                'id' => $sedationId,
                'name' => $sedationName,
            ];*/
        }

        return $data;
    }

    protected function getSedationFromAppointmentActivity($activityName)
    {
        if (!$this->sedations) {
            $this->sedations = $this->getSedationsInfo();
        }
        $activitySedations = array_column($this->sedations, null, 'pa2s_activity');
        if (isset($activitySedations[$activityName])) {
            return $activitySedations[$activityName]['pse_id_sedation'];
        }
        return null;
    }

    protected function getSedationName($sedationId)
    {
        if (!$this->sedations) {
            $this->sedations = $this->getSedationsInfo();
        }
        $sedations = array_column($this->sedations, 'pse_name', 'pse_id_sedation');
        if (isset($sedations[$sedationId])) {
            return $sedations[$sedationId];
        }
        return null;
    }

    protected function getSedationsInfo()
    {
        $select = $this->resultFetcher->getSelect('pulse__activity2sedation');
        $select->columns(['pa2s_activity'])
            ->join('pulse__sedations', 'pa2s_id_sedation = pse_id_sedation', ['pse_id_sedation', 'pse_name'])
            ->where([
                'pse_active' => 1
            ]);

        return $this->resultFetcher->fetchAll($select);
    }
}