<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use Pulse\Api\Emma\Fhir\Repository\AppointmentRepository;

/**
 * If no organization is known, put it in the escrow organization
 */
class AppointmentEscrowOrganizationTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $escrowOrganizationId = 81;


    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (!isset($row['gap_id_organization'])) {
            $row['gap_id_organization'] = $this->escrowOrganizationId;
        }

        return $row;
    }
}
