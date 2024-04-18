<?php

namespace Pulse\Api\Fhir\Model\Transformer;

class AppointmentIdentifierTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key => $row) {
            $identifiers = [];
            if (isset($row['gap_id_appointment'])) {
                $identifier = [
                    'use' => 'official',
                    'value' => $row['gap_id_appointment'],
                ];
                $identifiers[] = $identifier;
            }
            if (isset($row['gap_id_in_source'])) {
                if (isset($row['gap_source']) && $row['gap_source'] === 'emma') {
                    $identifier = [
                        'use' => 'secondary',
                        'type' => 'http://fhir.timeff.com/identifier/identificatienummer',
                        'value' => $row['gap_id_in_source'],
                    ];
                    $identifiers[] = $identifier;
                }
            }
            if (count($identifiers)) {
                $data[$key]['identifier'] = $identifiers;
            }
        }

        return $data;
    }
}