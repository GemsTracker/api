<?php

namespace Pulse\Api\Model\Transformer;


class ToManyTransformer extends \MUtil\Model\Transform\ToManyTransformer
{
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
        if (!$this->savable) {
            return;
        }

        if (! isset($row[$name])) {
            return;
        }

        $data = $row[$name];

        $child = reset($join);
        $parent = key($join);

        $parentId = $row[$parent];
        $filter = [$child => $parentId];
        $oldResults = $sub->load($filter);

        $saveRows = [];
        $deletedResults = [];

        $keys = $sub->getKeys();
        $key = reset($keys);

        $dataKeys = array_column($data, $key);

        foreach($oldResults as $oldResult) {
            $index = array_search($oldResult[$key], $dataKeys);
            if ($index !== false) {
                $saveRows[] = $oldResult;
                unset($data[$index]);
            } else {
                $deletedResults[] = $oldResult;
            }
        }

        foreach($data as $newValue) {
            $newValue[$child] = $parentId;
            $saveRows[] = $newValue;
        }

        $newResults = [];
        if (!empty($saveRows)) {
            $newResults = $sub->saveAll($saveRows);
        }

        $deleteIds = array_column($deletedResults, $key);
        if (!empty($deleteIds)) {
            foreach($deleteIds as $deleteId) {
                $sub->delete([$key => $deleteId]);
            }

        }

        $row[$name] = $newResults;
    }
}
