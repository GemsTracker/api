<?php

namespace Pulse\Api\Model\Transformer;

class RangeTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    private string $fieldName;

    public function __construct(
        string $fieldName
    )
    {
        $this->fieldName = $fieldName;
    }

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {        
        if (isset($filter['start'])) {
            $start = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $filter['start']);
            $filter[] = $this->fieldName . ' >= \'' . $start->format('Y-m-d H:i:s') .'\'';
            unset($filter['start']);
        }
        if (isset($filter['end'])) {
            $end = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $filter['end']);
            $filter[] = $this->fieldName . ' <= \'' . $end->format('Y-m-d H:i:s') .'\'';
            unset($filter['end']);
        }

        return $filter;
    }
}