<?php


namespace Pulse\Api\Repository;


use Zalt\Loader\ProjectOverloader;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Sql;

class RespondentTrackfieldsRepository
{
    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    public function __construct(\Gems_Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function getTrackfields($respondentTrackId)
    {
        $respondentTrack = $this->tracker->getRespondentTrack($respondentTrackId);

        $fieldData = $respondentTrack->getFieldData();
        $fieldCodes = $respondentTrack->getCodeFields();

        $fields = [];
        foreach ($fieldCodes as $fieldCode => $fieldValue) {
            if ($fieldCode && array_key_exists($fieldCode, $fieldData)) {
                $fields[$fieldCode] = $fieldData[$fieldCode];
            }
        }

        return $fields;
    }

    public function setTrackfields($respondentTrackId, $data)
    {
        $respondentTrack = $this->tracker->getRespondentTrack($respondentTrackId);

        $newFieldData = $respondentTrack->setFieldData($data);

        return [];
    }
}
