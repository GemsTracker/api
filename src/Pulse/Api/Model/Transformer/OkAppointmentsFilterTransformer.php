<?php

namespace Pulse\Api\Model\Transformer;

use Gems\Rest\Cache\Psr6CacheHelpers;
use Gems\Rest\Db\ResultFetcher;
use Psr\Cache\CacheItemPoolInterface;

class OkAppointmentsFilterTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    use Psr6CacheHelpers;

    public const OK_ACTIVITY_CACHE_KEY = 'ok_appointment_activities';
    private ResultFetcher $resultFetcher;

    public function __construct(
        CacheItemPoolInterface $cache,
        ResultFetcher $resultFetcher
    )
    {
        $this->cache = $cache;
        $this->resultFetcher = $resultFetcher;
    }

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        $filter['gap_id_activity'] = $this->getOkActivityIds();

        return $filter;
    }

    protected function getOkActivityIds()
    {
        if ($this->cache->hasItem(static::OK_ACTIVITY_CACHE_KEY)) {
            return $this->getCacheItem(static::OK_ACTIVITY_CACHE_KEY);
        }

        $select = $this->resultFetcher->getSelect('gems__agenda_activities');
        $select->columns(['gaa_id_activity']);
        $select->where->like('gaa_name', 'OK %');
        $select->where(['gaa_active' => 1]);

        $result = $this->resultFetcher->fetchCol($select);

        $this->setCacheItem(static::OK_ACTIVITY_CACHE_KEY, $result);

        return $result;
    }
}