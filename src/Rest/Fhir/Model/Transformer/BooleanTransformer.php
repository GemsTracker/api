<?php


namespace Gems\Rest\Fhir\Model\Transformer;


class BooleanTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * This transform function checks the filter for
     * a) retreiving filters to be applied to the transforming data,
     * b) adding filters that are needed
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $filter
     * @return array The (optionally changed) filter
     */
    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        foreach($this->fields as $field) {
            if (array_key_exists($field, $filter)) {
                $filter[$field] = (int) $filter[$field];
            }
        }
        return $filter;
    }

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
        foreach($data as $key=>$item) {
            foreach($this->fields as $field) {
                if (isset($item[$field])) {
                    $data[$key][$field] = (bool) $data[$key][$field];
                }
            }
        }

        return $data;
    }

}
