<?php


namespace Gems\Rest\Fhir\Model\Transformer;


class EpisodeOfCareDiagnosisTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach ($data as $key=>$item) {

        }

        return $data;
    }
}
