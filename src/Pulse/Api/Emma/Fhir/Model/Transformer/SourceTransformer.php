<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Pulse\Api\Emma\Fhir\Repository\EpdRepository;

class SourceTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    /**
     * @var string
     */
    protected $fieldName;

    public function __construct(EpdRepository $epdRepository, $fieldName)
    {
        $this->epdRepository = $epdRepository;
        $this->fieldName = $fieldName;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        $row[$this->fieldName] = $this->epdRepository->getEpdName();
        return $row;
    }
}
