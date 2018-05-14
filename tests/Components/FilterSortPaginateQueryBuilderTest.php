<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 13.05.2018 13:15
 */

namespace DjinORM\Repositories\Sql\Components;


use Aura\SqlQuery\QueryFactory;
use DjinORM\Components\FilterSortPaginate\Exceptions\UnsupportedFilterException;
use DjinORM\Components\FilterSortPaginate\Filters\BetweenFilter;
use DjinORM\Components\FilterSortPaginate\Filters\CompareFilter;
use DjinORM\Components\FilterSortPaginate\Filters\FilterInterface;
use DjinORM\Components\FilterSortPaginate\FilterSortPaginate;
use DjinORM\Components\FilterSortPaginate\FilterSortPaginateFactory;
use DjinORM\Components\FilterSortPaginate\Sort;
use PHPUnit\Framework\TestCase;

class FilterSortPaginateQueryBuilderTest extends TestCase
{

    /** @var array */
    private $query;

    /** @var QueryFactory */
    private $queryFactory;

    /** @var FilterSortPaginateFactory */
    private $fspFactory;

    /** @var FilterSortPaginate */
    private $fsp;

    /** @var \DjinORM\Repositories\Sql\Components\FilterSortPaginateQueryBuilder */
    private $fspQueryBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->query = [
            'paginate' => [
                'number' => 10,
                'size' => 50,
            ],
            'sort' => [
                'field_1' => Sort::SORT_DESC,
                'field_2' => Sort::SORT_ASC,
            ],
            'filters' => [
                '$or' => [
                    '$or' => [
                        '$and' => [
                            'field_1' => ['$between' => ['2018-01-01', '2018-12-31']],
                            'field_2' => ['$compare' => [CompareFilter::GREAT_THAN, 500]],
                            'field_3' => ['$empty' => true],
                            'field_4' => ['$empty' => false],
                            'field_5' => ['$equals' => 'value'],
                            //'field_6' => ['$fulltextSearch' => 'hello world'],
                            'field_7' => ['$in' => [1, 2, 3, 4, 'five', 'six']],
                            'field_8' => ['$wildcard' => '*hello ?????!'],
                            'field_9' => ['$notBetween' => [100, 200]],
                            'field_10' => ['$notEquals' => 'not-value'],
                            'field_11' => ['$notIn' => [9, 8, 7]],
                            'field_12' => ['$notWildcard' => '*hello ?????!'],
                        ],
                        'field_1' => [
                            '$empty' => false,
                            '$compare' => [CompareFilter::LESS_THAN, 10000],
                        ],
                    ],
                    'datetime' => ['$between' => ['2018-01-01', '2018-12-31']],
                ],
            ],
        ];
        $this->queryFactory = new QueryFactory(QueryFactory::COMMON);
        $this->fspFactory = new FilterSortPaginateFactory();
        $this->fsp = $this->fspFactory->create($this->query);
        $this->fspQueryBuilder = new FilterSortPaginateQueryBuilder($this->fsp);
    }

    public function testGetFilters()
    {
        $this->assertCount(11, $this->fspQueryBuilder->getFilters());
    }

    public function testAddFilter()
    {
        $this->fspQueryBuilder->addFilter(FilterInterface::class, function (){});
        $this->assertCount(12, $this->fspQueryBuilder->getFilters());
    }

    public function testRemoveFilter()
    {
        $this->fspQueryBuilder->removeFilter(BetweenFilter::class);
        $this->assertCount(10, $this->fspQueryBuilder->getFilters());
    }

    public function testBuildQuery()
    {
        $fsp = $this->fspFactory->create($this->query);
        $fspQueryBuilder = new FilterSortPaginateQueryBuilder($fsp);

        $actual = $fspQueryBuilder->buildQuery($this->queryFactory->newSelect()->cols(['*']));

        $expected = $this->queryFactory->newSelect()->cols(['*']);
        $expected->page(10);
        $expected->setPaging(50);
        $expected->orderBy(['field_1 DESC', 'field_2 ASC']);

        $expected->where('
            (
                (
                    (
                        field_1 BETWEEN :betweenFirst_3 AND :betweenLast_3 AND
                        field_2 > :compare_4 AND
                        (field_3 IS NULL OR field_3 = :empty_5) AND
                        (field_4 IS NOT NULL AND field_4 != :empty_6) AND
                        field_5 = :equals_7 AND
                        field_7 IN(:in_8) AND
                        field_8 LIKE :wildcard_9 AND
                        field_9 NOT BETWEEN :betweenFirst_10 AND :betweenLast_10 AND
                        field_10 != :notEquals_11 AND
                        field_11 NOT IN(:notIn_12) AND
                        field_12 NOT LIKE :wildcard_13
                    )
                    OR
                    (
                        (field_1 IS NOT NULL AND field_1 != :empty_15) AND
                        field_1 < :compare_16
                    )
                )
                OR
                datetime BETWEEN :betweenFirst_17 AND :betweenLast_17
            )
        ');

        $this->assertEquals(
            preg_replace('~\s+~', ' ', trim($expected->getStatement())),
            preg_replace('~\s+~', ' ', trim($actual->getStatement()))
        );

        $this->assertEquals([
            'betweenFirst_3' => '2018-01-01',
            'betweenLast_3' => '2018-12-31',
            'compare_4' => 500,
            'empty_5' => '',
            'empty_6' => '',
            'equals_7' => 'value',
            'in_8' => [1, 2, 3, 4, 'five', 'six'],
            'wildcard_9' => '%hello _____!',
            'betweenFirst_10' => 100,
            'betweenLast_10' => 200,
            'notEquals_11' => 'not-value',
            'notIn_12' => [9, 8, 7],
            'wildcard_13' => '%hello _____!',
            'empty_15' => '',
            'compare_16' => '10000',
            'betweenFirst_17' => '2018-01-01',
            'betweenLast_17' => '2018-12-31',
        ], $actual->getBindValues());
    }

    public function testUnsupportedFilter()
    {
        $this->expectException(UnsupportedFilterException::class);
        $this->query['filters']['$or']['field_2'] = ['$unsupported' => 10];
        $this->fspFactory->create($this->query);
    }

    public function testBuildWithoutSortAndFilters()
    {
        $fsp = new FilterSortPaginate();
        $fspQueryBuilder = new FilterSortPaginateQueryBuilder($fsp);
        $actual = $fspQueryBuilder->buildQuery($this->queryFactory->newSelect()->cols(['*']));

        $expected = $this->queryFactory->newSelect()->cols(['*']);

        $this->assertEquals($expected->getStatement(), $actual->getStatement());
        $this->assertEquals($expected->getBindValues(), $actual->getBindValues());
    }
}
