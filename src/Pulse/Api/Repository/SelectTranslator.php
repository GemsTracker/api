<?php


namespace Pulse\Api\Repository;


use Psr\Http\Message\ServerRequestInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Select;

class SelectTranslator
{
    protected $keywords = [
        'per_page',
        'page',
        'order',
    ];

    protected $routeOptions = [];
    /**
     * @var Adapter
     */
    protected $db;

    public function __construct(Adapter $db)
    {

        $this->db = $db;
    }

    public function addRequestParamsToSelect(Select $select, $params, $translations = [])
    {
        $keywords = array_flip($this->keywords);

        $filters = [];

        foreach($params as $key=>$value) {
            if (isset($keywords[$key])) {
                continue;
            }

            /*if (isset($this->routeOptions['multiOranizationField'], $this->routeOptions['multiOranizationField']['field'])
                && $key == $this->routeOptions['multiOranizationField']['field']) {
                $field = $this->routeOptions['multiOranizationField']['field'];
                $separator = $this->routeOptions['multiOranizationField']['separator'];
                $organizationIds = $value;
                if (!is_array($organizationIds)) {
                    $organizationIds = explode(',', $organizationIds);
                }

                $organizationFilter = [];
                foreach($organizationIds as $organizationId) {
                    $organizationFilter[] = $field . ' LIKE '. $this->db1->quote('%'.$separator . $organizationId . $separator . '%');
                }
                if (!empty($organizationFilter)) {
                    $filters[] = '(' . join(' OR ', $organizationFilter) . ')';
                }

                continue;
            }*/

            $colName = $key;
            if (isset($translations[$colName])) {
                $colName = $translations[$colName];
            }


            if (is_string($value) || is_numeric($value)) {
                if (strpos($value, '[') === 0 && strpos($value, ']') === strlen($value) - 1) {
                    $values = explode(',', str_replace(['[', ']'], '', $value));
                    $operator = reset($values);
                    if (count($values) > 1) {
                        $compareValue = end($values);
                        if (is_numeric($compareValue)) {
                            $compareValue = ($compareValue == (int)$compareValue) ? (int)$compareValue : (float)$compareValue;
                        }
                    }
                    switch ($operator) {
                        case '<':
                            $select->where->lessThan($colName, $compareValue);
                            break;
                        case '>':
                            $select->where->greaterThan($colName, $compareValue);
                            break;
                        case '<=':
                            $select->where->lessThanOrEqualTo($colName, $compareValue);
                            break;
                        case '>=':
                            $select->where->greaterThanOrEqualTo($colName, $compareValue);
                            break;
                        case '!=':
                            $select->where->notEqualTo($colName, $compareValue);
                            break;
                        case 'LIKE':
                            $select->where->like($colName,$compareValue);
                            break;
                        case 'NOT LIKE':
                            $select->where->notLike($colName, $compareValue);
                            break;
                        default:
                            foreach($values as $key=>$value) {
                                if (is_numeric($compareValue)) {
                                    $values[$key] = ($compareValue == (int)$compareValue) ? (int)$compareValue : (float)$compareValue;
                                }
                                if ($value === '') {
                                    unset($values[$key]);
                                }
                            }
                            $select->where->in($colName, $values);
                            break;
                    }
                } else {
                    switch (strtoupper($value)) {
                        case 'IS NULL':
                            $select->where->isNull($colName);
                            break;
                        case 'IS NOT NULL':
                            $select->where->isNotNull($colName);
                            break;
                        default:
                            $select->where([$colName => $value]);
                            break;
                    }
                }
            } elseif (is_array($value)) {
                $select->where([$colName => $value]);
            }
        }

        return $select;
    }
}
