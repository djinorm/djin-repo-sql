<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 11.05.2018 15:59
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\Helpers;


use Aura\SqlQuery\Common\SelectInterface;
use DjinORM\Components\FilterSortPaginate\Exceptions\UnsupportedFilterException;
use DjinORM\Components\FilterSortPaginate\Filters\AndFilter;
use DjinORM\Components\FilterSortPaginate\Filters\BetweenFilter;
use DjinORM\Components\FilterSortPaginate\Filters\CompareFilter;
use DjinORM\Components\FilterSortPaginate\Filters\EmptyFilter;
use DjinORM\Components\FilterSortPaginate\Filters\EqualsFilter;
use DjinORM\Components\FilterSortPaginate\Filters\FilterInterface;
use DjinORM\Components\FilterSortPaginate\Filters\InFilter;
use DjinORM\Components\FilterSortPaginate\Filters\NotBetweenFilter;
use DjinORM\Components\FilterSortPaginate\Filters\NotEmptyFilter;
use DjinORM\Components\FilterSortPaginate\Filters\NotEqualsFilter;
use DjinORM\Components\FilterSortPaginate\Filters\NotInFilter;
use DjinORM\Components\FilterSortPaginate\Filters\NotWildcardFilter;
use DjinORM\Components\FilterSortPaginate\Filters\OrFilter;
use DjinORM\Components\FilterSortPaginate\Filters\WildcardFilter;
use DjinORM\Components\FilterSortPaginate\FilterSortPaginate;

class FilterSortPaginateHelper
{

    private static $iteration = 0;

    /**
     * @param FilterSortPaginate $fsp
     * @param SelectInterface $select
     * @return SelectInterface
     * @throws UnsupportedFilterException
     */
    public static function buildQuery(FilterSortPaginate $fsp, SelectInterface $select): SelectInterface
    {
        self::$iteration = 0;
        $select
            ->setPaging($fsp->getPageSize())
            ->page($fsp->getPageNumber());

        if ($fsp->getSort()) {
            foreach ($fsp->getSort()->get() as $sortBy => $sortDirection) {
                $select->orderBy(["{$sortBy} " . ($sortDirection == 1 ? 'ASC' : 'DESC')]);
            }
        }

        if ($fsp->getFilter()) {
            $expression = static::filter($fsp->getFilter());
            $condition = preg_replace('~\s+~', ' ', trim($expression[0]));
            $select->where($condition);
            $select->bindValues($expression[1]);
        }

        return $select;
    }

    /**
     * @param FilterInterface $filter
     * @return array
     * @throws UnsupportedFilterException
     */
    protected static function filter(FilterInterface $filter): array
    {
        $postfix = '_' . self::$iteration++;
        $class = get_class($filter);
        $params = [];

        switch ($class) {
            case AndFilter::class:
                /** @var AndFilter $filter */
                $conditions = [];
                foreach ($filter->getFilters() as $filter) {
                    $expression = static::filter($filter);
                    $conditions[] = $expression[0];
                    $params = array_merge($params, $expression[1]);
                }
                $condition = " (" . implode(" AND ", $conditions) . ") ";
                break;
            case OrFilter::class:
                /** @var OrFilter $filter */
                $conditions = [];
                foreach ($filter->getFilters() as $filter) {
                    $expression = static::filter($filter);
                    $conditions[] = $expression[0];
                    $params = array_merge($params, $expression[1]);
                }
                $condition = " (" . implode(" OR ", $conditions) . ") ";
                break;


            case BetweenFilter::class:
                /** @var BetweenFilter $filter */
                $condition = "{$filter->getField()} BETWEEN :betweenFirst{$postfix} AND :betweenLast{$postfix}";
                $params['betweenFirst' . $postfix] = $filter->getFirstValue();
                $params['betweenLast' . $postfix] = $filter->getLastValue();
                break;
            case CompareFilter::class:
                /** @var CompareFilter $filter */
                $condition = "{$filter->getField()} {$filter->getComparator()} :compare{$postfix}";
                $params['compare' . $postfix] = $filter->getValue();
                break;
            case EmptyFilter::class:
                /** @var EmptyFilter $filter */
                $condition = "({$filter->getField()} IS NULL OR {$filter->getField()} = :empty{$postfix})";
                $params['empty' . $postfix] = '';
                break;
            case EqualsFilter::class:
                /** @var EqualsFilter $filter */
                $condition = "{$filter->getField()} = :equals{$postfix}";
                $params['equals' . $postfix] = $filter->getValue();
                break;
            case InFilter::class:
                /** @var InFilter $filter */
                $condition = "{$filter->getField()} IN(:in{$postfix})";
                $params['in' . $postfix] = $filter->getValues();
                break;
            case WildcardFilter::class:
                /** @var WildcardFilter $filter */
                $condition = "{$filter->getField()} LIKE :wildcard{$postfix}";
                $like = str_replace([WildcardFilter::ANY, WildcardFilter::ONE], ['%', '_'], $filter->getWildcard());
                $params['wildcard' . $postfix] = $like;
                break;


            case NotBetweenFilter::class:
                /** @var NotBetweenFilter $filter */
                $condition = "{$filter->getField()} NOT BETWEEN :betweenFirst{$postfix} AND :betweenLast{$postfix}";
                $params['betweenFirst' . $postfix] = $filter->getFirstValue();
                $params['betweenLast' . $postfix] = $filter->getLastValue();
                break;
            case NotEmptyFilter::class:
                /** @var NotEmptyFilter $filter */
                $condition = "({$filter->getField()} IS NOT NULL AND {$filter->getField()} != :empty{$postfix})";
                $params['empty' . $postfix] = '';
                break;
            case NotEqualsFilter::class:
                /** @var NotEqualsFilter $filter */
                $condition = "{$filter->getField()} != :notEquals{$postfix}";
                $params['notEquals' . $postfix] = $filter->getValue();
                break;
            case NotInFilter::class:
                /** @var NotInFilter $filter */
                $condition = "{$filter->getField()} NOT IN(:notIn{$postfix})";
                $params['notIn' . $postfix] = $filter->getValues();
                break;
            case NotWildcardFilter::class:
                /** @var NotWildcardFilter $filter */
                $condition = "{$filter->getField()} NOT LIKE :wildcard{$postfix}";
                $like = str_replace([WildcardFilter::ANY, WildcardFilter::ONE], ['%', '_'], $filter->getWildcard());
                $params['wildcard' . $postfix] = $like;
                break;


            default:
                throw new UnsupportedFilterException("Filter «{$class}» was nat supported by this implementing");
        }

        return [" {$condition} ", $params];
    }

}