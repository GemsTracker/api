<?php

namespace Prediction\Model\Transform;

class NestedTransformerWithFilter extends \MUtil_Model_Transform_NestedTransformer
{
    public $deleteMissingRowsOnSave = true;

    /**
     * Function to allow overruling of transform for certain models
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param \MUtil_Model_ModelAbstract $sub
     * @param array $data
     * @param array $join
     * @param string $name
     */
    protected function transformSaveSubModel(
        \MUtil_Model_ModelAbstract $model, \MUtil_Model_ModelAbstract $sub, array &$row, array $join, $name)
    {
        if ($this->skipSave) {
            return;
        }

        if (! isset($row[$name])) {
            return;
        }

        $data = $row[$name];
        $keys = [];

        // Get the parent key values.
        foreach ($join as $parent => $child) {
            if (isset($row[$parent])) {
                $keys[$child] = $row[$parent];
            } else {
                // if there is no parent identifier set, don't save
                return;
            }
        }
        
        foreach($data as $key => $subrow) {
            // Make sure the (possibly changed) parent key
            // is stored in the sub data.
            $data[$key] = $keys + $subrow;
        }


        // Delete missing rows, presuming all required rows are supplied on save
        if ($this->deleteMissingRowsOnSave) {
            $subEntries = $sub->load($keys);
            $subKeys = $sub->getKeys();

            $deleteItems = $this->getDeleteItems($subEntries, $data, $subKeys);

            foreach ($deleteItems as $index => $values) {
                $deleteFilter = $keys;
                $deleteFilter += $values;
                $sub->delete($deleteFilter);
            }
        }

        $saved = $sub->saveAll($data);

        $row[$name] = $saved;
    }

    /**
     * Prepare a diff row for getDeleteItems by filtering the keys, sorting the items and serializing the result
     *
     * @param $row row with values
     * @param $keys filter keys
     * @return string serialized row
     */
    protected function prepareDiffRow($row, $keys=null)
    {
        if ($keys !== null) {
            $keys = array_flip(array_values($keys));
            $row = array_intersect_key($row, $keys);
        }
        ksort($row);
        return serialize($row);
    }

    /**
     * Get items that exist in an original dataset, but are missing from the compare dataset.
     * If compare keys are supplied only those keys are checked (e.g. only check DB key columns)
     * If returnFiltered is true the items returned will only have the keys from the compare keys, else the whole items are returned
     *
     * @param $originalDataSet list of original items
     * @param $compareDataSet list of items to diff the original dataset with
     * @param $compareKeys array list of keys that should be compared
     * @param bool $returnFiltered return compare key filtered dataset or full dataset
     * @return array array List of items that are not present in the compare dataset
     */
    protected function getDeleteItems($originalDataSet, $compareDataSet, $compareKeys=null, $returnFiltered=true)
    {
        $callback = function($row) use ($compareKeys) { return $this->prepareDiffRow($row, $compareKeys); };

        //$test1 = array_map($callback, $originalDataSet);
        //$test2 = array_map($callback, $compareDataSet);

        $diffKeys = array_map('unserialize', array_diff(array_map($callback, $originalDataSet), array_map($callback, $compareDataSet)));

        if ($returnFiltered) {
            return $diffKeys;
        }
        return array_intersect_key($originalDataSet, $diffKeys);
    }
}