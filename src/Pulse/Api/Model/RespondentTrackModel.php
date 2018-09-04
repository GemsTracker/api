<?php


namespace Pulse\Api\Model;


class RespondentTrackModel extends \Gems_Tracker_Model_RespondentTrackModel
{
    /**
     * The tables that can be saved with the table name as key and the \MUtil_Model_DatabaseModelAbstract SAVE_MODE
     *
     * @return array|null
     */
    public function getSaveTables()
    {
        return $this->_saveTables;
    }
}