<?php


namespace Pulse\Api\Repository;


use Zalt\Loader\ProjectOverloader;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;

class TrackfieldsRepository
{
    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    public function __construct(\Gems_Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function getTrackfields($trackId)
    {
        $trackEngine = $this->tracker->getTrackEngine($trackId);

        $fieldMaintenanceModel = $trackEngine->getFieldsMaintenanceModel();
        $trackFields = $fieldMaintenanceModel->load(['gtf_id_track' => $trackId]);

        $codeTrackFields = [];
        foreach($trackFields as $trackfield) {
            if (isset($trackfield['gtf_field_code']) && $trackfield['gtf_field_code'] !== null) {
                $codeTrackFields[] = $trackfield;
            }
        }


        return $codeTrackFields;
    }
}