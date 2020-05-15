<?php


namespace Gems\Rest\Fhir\Model\Transformer;


class ManagingOrganizationTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * Field in model pointing to organization ID
     *
     * @var string
     */
    protected $organizationIdField;

    /**
     * Is the gems__organization table joined in the model?
     *
     * @var bool
     */
    protected $organizationJoined;

    public function __construct($organizationIdField, $organizationJoined=true)
    {
        $this->organizationIdField = $organizationIdField;
        $this->organizationJoined = $organizationJoined;
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
        if (isset($filter['managingOrganization'])) {
            $value = (int)str_replace($this->getOrganizationEndpoint(), '', $filter['managingOrganization']);
            $filter[$this->organizationIdField] = $value;

            unset($filter['managingOrganization']);
        }
        if (isset($filter['organization'])) {
            $value = (int)str_replace($this->getOrganizationEndpoint(), '', $filter['organization']);
            $filter[$this->organizationIdField] = $value;

            unset($filter['organization']);
        }

        if ($this->organizationJoined) {
            if (isset($filter['organization_name'])) {
                $value = $filter['organization_name'];
                $filter[] = "gor_name LIKE '%" . $value . "%'";

                unset($filter['organization_name']);
            }

            if (isset($filter['organization_code'])) {
                $value = $filter['organization_code'];
                $filter['gor_code'] = $value;

                unset($filter['organization_code']);
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
        foreach ($data as $key => $item) {
            $data[$key]['managingOrganization']['id'] = $item[$this->organizationIdField];
            $data[$key]['managingOrganization']['reference'] = $this->getOrganizationEndpoint() . $item[$this->organizationIdField];
        }

        return $data;
    }

    protected function getOrganizationEndpoint()
    {
        return 'fhir/organization/';
    }
}
