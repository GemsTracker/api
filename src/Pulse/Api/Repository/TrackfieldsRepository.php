<?php


namespace Pulse\Api\Repository;


use Zalt\Loader\ProjectOverloader;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;

class TrackfieldsRepository
{
    protected $allowedFields = [
        'gtf_id_field',
        'gtf_field_type',
        'gtf_field_name',
        'gtf_field_code',
        'gtf_id_order',
        'gtf_field_values',
        'gtf_required',
        'gtf_readonly'
    ];

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

        $allowedFields = array_flip($this->allowedFields);

        $codeTrackFields = [];
        foreach($trackFields as $trackfield) {
            if (isset($trackfield['gtf_field_code']) && $trackfield['gtf_field_code'] !== null) {
                $codeTrackFields[] = array_intersect_key($trackfield, $allowedFields);
            }
        }


        return $codeTrackFields;
    }
}