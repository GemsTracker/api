<?php


namespace Pulse\Api\Model;


class RespondentModel extends \Pulse_Model_RespondentModel
{
    /**
     * Skip the Respondent check organization ID as it is already done in the API
     *
     * @param mixed $filter True for the filter stored in this model or a filter array
     * @return array The filter to use
     */
    protected function _checkFilterUsed($filter)
    {
        $filter = \MUtil_Model_ModelAbstract::_checkFilterUsed($filter);

        if ($this->isMultiOrganization() && !isset($filter['gr2o_id_organization'])) {
            $allowed = array_keys($this->currentUser->getAllowedOrganizations());

            if (!in_array($filter['gr2o_id_organization'], $allowed)) {
                $filter['gr2o_id_organization'] = $allowed;
            }
        }

        return $filter;
    }
}