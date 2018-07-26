<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 17.07.2018 18:02
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql;

use DjinORM\Components\FilterSortPaginate\Filters\AndFilter;
use DjinORM\Components\FilterSortPaginate\Filters\CompareFilter;
use DjinORM\Components\FilterSortPaginate\Filters\EqualsFilter;
use DjinORM\Components\FilterSortPaginate\FilterSortPaginate;
use DjinORM\Components\FilterSortPaginate\Sort;
use DjinORM\Djin\Id\MemoryIdGenerator;
use DjinORM\Djin\Model\ModelInterface;
use DjinORM\Repositories\Sql\Components\DbTestCase;
use DjinORM\Repositories\Sql\Fakes\MappedSqlRepo;
use DjinORM\Repositories\Sql\Fakes\Model;
use DjinORM\Repositories\Sql\Fakes\Money;
use DjinORM\Repositories\Sql\Fakes\NestedModel;
use PHPUnit\DbUnit\DataSet\ArrayDataSet;

class MappedSqlRepoTest extends DbTestCase
{

    /** @var MappedSqlRepo */
    private $repo;

    protected function setUp()
    {
        parent::setUp();
        $this->repo = new MappedSqlRepo(
            $this->getPdo(),
            $this->getQueryFactory(),
            new MemoryIdGenerator(100)
        );
    }

    public function testFindById()
    {
        /** @var Model $model */
        $model = $this->repo->findById(1);
        $this->assertInstanceOf(Model::class, $model);
        $this->assertEquals(1, $model->getId()->toScalar());
        $this->assertEquals('first', $model->name);

        $this->assertEquals(
            ['one' => 1,'two' => 2,'array' => [1, 2, 3]],
            $model->Array
        );

        $this->assertEquals(
            [new Money(111, 'USD'), new Money(222, 'RUB')],
            $model->Balances
        );

        $this->assertEquals(new Money(100, 'RUB'), $model->Money);

        $this->assertInstanceOf(NestedModel::class, $model->Nested);
        $this->assertEquals(new Money(1000, 'USD'), $model->Nested->Money);

        $this->assertEquals(
            [
                'one' => 11,
                'two' => 22,
                'array' => [11, 22, 33]
            ],
            $model->Nested->Array
        );

        $this->assertInstanceOf(NestedModel::class, $model->Nested_must);
        $this->assertEquals(new Money(7777, 'UAH'), $model->Nested_must->Money);

        $this->assertEquals(
            [
                'one' => 111,
                'two' => 222,
                'array' => [111, 222, 333]
            ],
            $model->Nested_must->Array
        );
    }

    public function testInsert()
    {
        $model = new Model(1001, 'Name 1001');
        $model->Array = [];
        $model->Nested_must = new NestedModel();
        $this->repo->insert($model);
        $this->assertModelSaved($model);

        $model = new Model(1002, 'Name 1002');
        $model->Array = [];
        $model->Nested = new NestedModel();
        $model->Nested->Array = [1, 2, 3];
        $model->Nested_must = new NestedModel();
        $this->repo->insert($model);
        $this->assertModelSaved($model);
    }

    public function testUpdate()
    {
        /** @var Model $model */
        $model = $this->repo->findById(1);
        $model->name = 'First Model';
        $model->Money = null;
        $this->repo->update($model);
        $this->assertModelSaved($model);

        /** @var Model $model */
        $model = $this->repo->findById(1);
        $model->Nested->Money = null;
        $this->repo->update($model);
        $this->assertModelSaved($model);

        /** @var Model $model */
        $model = $this->repo->findById(1);
        $model->Nested = null;
        $this->repo->update($model);
        $this->assertModelSaved($model);
    }

    public function testQuoter()
    {
        $fsp = new FilterSortPaginate(
            null,
            new Sort(['Nested.Money.Amount' => Sort::SORT_ASC]),
            new AndFilter([
                new EqualsFilter('Nested.Money', 1),
                new CompareFilter('Nested_must.Money.Amount', CompareFilter::GREAT_THAN, 1)
            ])
        );

        $this->repo->findWithFilterSortPaginate($fsp);

        $expected = 'SELECT * FROM `djin-repo` WHERE ( nested___money = :equals_1 AND nested_must___money___amount > :compare_2 ) ORDER BY nested___money___amount ASC';
        $actual = preg_replace('~\s+~', ' ', $this->repo->lastQuery->getStatement());
        $this->assertEquals($expected, $actual);
    }

    protected function assertModelSaved(ModelInterface $model)
    {
        $this->repo->freeUpMemory();
        $foundModel = $this->repo->findById($model->getId()->toScalar());
        $this->assertEquals($model, $foundModel);
    }

    protected function getDataSet(): ArrayDataSet
    {
        return new ArrayDataSet([
            'djin-repo' => [
                [
                    'id' => 1,
                    'name' => 'first',
                    'array' => json_encode([
                        'one' => 1,
                        'two' => 2,
                        'array' => [1, 2, 3]
                    ]),
                    'balances' => json_encode([
                        [
                            'amount' => 111,
                            'currency' => 'USD'
                        ],
                        [
                            'amount' => 222,
                            'currency' => 'RUB'
                        ],
                    ]),
                    'money' => 1,
                    'money___amount' => 100,
                    'money___currency' => 'RUB',
                    'nested' => 1,
                    'nested___money' => 1,
                    'nested___money___amount' => 1000,
                    'nested___money___currency' => 'USD',
                    'nested___array' => json_encode([
                        'one' => 11,
                        'two' => 22,
                        'array' => [11, 22, 33]
                    ]),
                    'nested_must' => 1,
                    'nested_must___money' => 1,
                    'nested_must___money___amount' => 7777,
                    'nested_must___money___currency' => 'UAH',
                    'nested_must___array' => json_encode([
                        'one' => 111,
                        'two' => 222,
                        'array' => [111, 222, 333]
                    ]),
                ],
            ],
        ]);
    }

}
