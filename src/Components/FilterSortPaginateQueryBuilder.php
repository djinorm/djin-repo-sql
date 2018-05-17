<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 11.05.2018 15:59
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\Components;


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

class FilterSortPaginateQueryBuilder
{

    /**
     * @var int
     */
    protected $iteration = 0;
    /**
     * @var FilterSortPaginate
     */
    protected $fsp;

    private $filters = [];

    public function __construct(FilterSortPaginate $fsp)
    {
        $this->fsp = $fsp;

        $this->filters = [
            BetweenFilter::class => function (string $postfix, BetweenFilter $filter){
                return [
                    "{$filter->getField()} BETWEEN :betweenFirst{$postfix} AND :betweenLast{$postfix}",
                    [
                        'betweenFirst' . $postfix => $filter->getFirstValue(),
                        'betweenLast' . $postfix => $filter->getLastValue(),
                    ]
                ];
            },
            CompareFilter::class => function (string $postfix, CompareFilter $filter){
                return [
                    "{$filter->getField()} {$filter->getComparator()} :compare{$postfix}",
                    ['compare' . $postfix => $filter->getValue()]
                ];
            },
            EmptyFilter::class => function (string $postfix, EmptyFilter $filter){
                return [
                    "({$filter->getField()} IS NULL OR {$filter->getField()} = :empty{$postfix})",
                    ['empty' . $postfix => '']
                ];
            },
            EqualsFilter::class => function (string $postfix, EqualsFilter $filter){
                return [
                    "{$filter->getField()} = :equals{$postfix}",
                    ['equals' . $postfix => $filter->getValue()]
                ];
            },
            InFilter::class => function (string $postfix, InFilter $filter){
                return [
                    "{$filter->getField()} IN(:in{$postfix})",
                    ['in' . $postfix => $filter->getValues()]
                ];
            },
            WildcardFilter::class => function (string $postfix, WildcardFilter $filter){
                $like = str_replace([WildcardFilter::ANY, WildcardFilter::ONE], ['%', '_'], $filter->getWildcard());
                return [
                    "{$filter->getField()} LIKE :wildcard{$postfix}",
                    ['wildcard' . $postfix =>  $like]
                ];
            },
            NotBetweenFilter::class => function (string $postfix, NotBetweenFilter $filter){
                return [
                    "{$filter->getField()} NOT BETWEEN :betweenFirst{$postfix} AND :betweenLast{$postfix}",
                    [
                        'betweenFirst' . $postfix => $filter->getFirstValue(),
                        'betweenLast' . $postfix => $filter->getLastValue(),
                    ]
                ];
            },
            NotEmptyFilter::class => function (string $postfix, NotEmptyFilter $filter){
                return [
                    "({$filter->getField()} IS NOT NULL AND {$filter->getField()} != :empty{$postfix})",
                    ['empty' . $postfix => '']
                ];
            },
            NotEqualsFilter::class => function (string $postfix, NotEqualsFilter $filter){
                return [
                    "{$filter->getField()} != :notEquals{$postfix}",
                    ['notEquals' . $postfix => $filter->getValue()]
                ];
            },
            NotInFilter::class => function (string $postfix, NotInFilter $filter){
                return [
                    "{$filter->getField()} NOT IN(:notIn{$postfix})",
                    ['notIn' . $postfix => $filter->getValues()]
                ];
            },
            NotWildcardFilter::class => function (string $postfix, NotWildcardFilter $filter){
                $like = str_replace([WildcardFilter::ANY, WildcardFilter::ONE], ['%', '_'], $filter->getWildcard());
                return [
                    "{$filter->getField()} NOT LIKE :wildcard{$postfix}",
                    ['wildcard' . $postfix => $like]
                ];
            },
        ];
    }

    /**
     * @return callable[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function addFilter(string $classname, callable $callback)
    {
        $this->filters[$classname] = $callback;
    }

    public function removeFilter(string $classname)
    {
        unset($this->filters[$classname]);
    }

    /**
     * @param SelectInterface $select
     * @return SelectInterface
     * @throws UnsupportedFilterException
     */
    public function buildQuery(SelectInterface $select): SelectInterface
    {
        $this->iteration = 0;

        $fsp = $this->fsp;

        if ($fsp->getPaginate()) {
            $select
                ->setPaging($fsp->getPaginate()->getSize())
                ->page($fsp->getPaginate()->getNumber());
        }

        if ($fsp->getSort()) {
            foreach ($fsp->getSort()->get() as $sortBy => $sortDirection) {
                $select->orderBy(["{$sortBy} " . ($sortDirection == 1 ? 'ASC' : 'DESC')]);
            }
        }

        if ($fsp->getFilter()) {
            $expression = $this->filter($fsp->getFilter());
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
    protected function filter(FilterInterface $filter): array
    {
        $postfix = '_' . $this->iteration++;
        $class = get_class($filter);
        $params = [];

        switch ($class) {
            case AndFilter::class:
                /** @var AndFilter $filter */
                $conditions = [];
                foreach ($filter->getFilters() as $filter) {
                    $expression = $this->filter($filter);
                    $conditions[] = $expression[0];
                    $params = array_merge($params, $expression[1]);
                }
                $condition = " (" . implode(" AND ", $conditions) . ") ";
                break;
            case OrFilter::class:
                /** @var OrFilter $filter */
                $conditions = [];
                foreach ($filter->getFilters() as $filter) {
                    $expression = $this->filter($filter);
                    $conditions[] = $expression[0];
                    $params = array_merge($params, $expression[1]);
                }
                $condition = " (" . implode(" OR ", $conditions) . ") ";
                break;

            default:
                $condition = null;
                $params = null;
                foreach ($this->filters as $filterClass => $callback) {
                    if ($class == $filterClass) {
                        $result = $callback($postfix, $filter);
                        $condition = $result[0];
                        $params = $result[1];
                        break;
                    }
                }
                if ($condition === null && $params === null) {
                    throw new UnsupportedFilterException("Filter «{$class}» was nat supported by this implemention");
                }
        }

        return [" {$condition} ", $params];
    }

}