<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 11.05.2018 17:17
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\Helpers;

use DjinORM\Components\FilterSortPaginate\Filters\AndFilter;
use DjinORM\Components\FilterSortPaginate\Filters\BetweenFilter;
use DjinORM\Components\FilterSortPaginate\Filters\CompareFilter;
use DjinORM\Components\FilterSortPaginate\Filters\EmptyFilter;
use DjinORM\Components\FilterSortPaginate\Filters\InFilter;
use DjinORM\Components\FilterSortPaginate\Filters\NotEqualsFilter;
use DjinORM\Components\FilterSortPaginate\Filters\OrFilter;
use DjinORM\Components\FilterSortPaginate\Filters\WildcardFilter;
use PHPUnit\Framework\TestCase;

class FilterSortPaginateHelperTest extends TestCase
{

    public function testFilter()
    {
        $filter = new AndFilter([
            new AndFilter([
                new EmptyFilter('firstName'),
                new BetweenFilter('createdAt', '2017-01-01', '2018-01-01'),
                new CompareFilter('total', CompareFilter::GREAT_OR_EQUALS_THAN, 15000),
                new WildcardFilter('comment', '*hello-???'),
            ]),
            new OrFilter([
                new InFilter('statusId', [1, 2, 3]),
                new NotEqualsFilter('additional_1', 5)
            ])
        ]);

        var_dump(FilterSortPaginateHelper::filter($filter));
    }
}
