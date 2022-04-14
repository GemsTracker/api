<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;

/**
 * Translate episode period
 */
class EpisodeOfCarePeriodTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (!isset($row['period'], $row['period']['start'])) {
            throw new MissingDataException('No Episode of care start period set');
        }

        $row['gec_startdate'] = $row['period']['start'];
        $row['gec_enddate'] = null;
        if (isset($row['period']['end'])) {
            $row['gec_enddate'] = $row['period']['end'];
        }

        return $row;
    }
}
