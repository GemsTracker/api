<?php


namespace Gems\Rest\Fhir\Model\Transformer;


class LocationTelecomTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param \MUtil_Model_ModelAbstract $model The parent model
     * @param array $data Nested array
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach ($data as $key => $item) {
            $elements = [];
            if (isset($item['glo_url'])) {
                $elements[] = ['system' => 'url', 'value' => $item['glo_url']];
            }

            $data[$key]['telecom'] = $elements;
        }

        return $data;
    }
}
