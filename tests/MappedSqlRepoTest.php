<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 17.07.2018 18:02
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql;

use DjinORM\Components\FilterSortPaginate\Filters\AndFilter;
use DjinORM\Components\FilterSortPaginate\Filters\CompareFilter;
use DjinORM\Components\FilterSortPaginate\Filters\WildcardFilter;
use DjinORM\Components\FilterSortPaginate\FilterSortPaginate;
use DjinORM\Components\FilterSortPaginate\Paginate;
use DjinORM\Components\FilterSortPaginate\Sort;
use DjinORM\Djin\Exceptions\ExtractorException;
use DjinORM\Djin\Id\MemoryIdGenerator;
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

    /** @var FilterSortPaginate */
    private $fsp;

    protected function setUp()
    {
        parent::setUp();
        $this->repo = new MappedSqlRepo(
            $this->getPdo(),
            $this->getQueryFactory(),
            new MemoryIdGenerator(100)
        );

        $sort = new Sort();
        $sort->add('id', Sort::SORT_DESC);
        $sort->add('name', Sort::SORT_ASC);

        $this->fsp = new FilterSortPaginate(new Paginate(1, 5), $sort, new AndFilter([
            new WildcardFilter('name', '*th'),
            new CompareFilter('id', CompareFilter::GREAT_THAN, 1)
        ]));
    }

    public function testFindById()
    {
        $this->assertEquals(0, $this->repo->getQueryCount());

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

        $this->assertEquals(1, $this->repo->getQueryCount());

        $this->repo->findById(1);
        $this->repo->findById(2);

        $this->assertEquals(2, $this->repo->getQueryCount());
    }

    public function testFindAll()
    {
        /** @var Model[]|array $models */
        $models = $this->repo->findAll();

        $this->assertCount(10, $models);

        $this->assertInstanceOf(Model::class, $models[0]);
        $this->assertEquals(1, $models[0]->getId()->toScalar());

        $this->assertInstanceOf(Model::class, $models[1]);
        $this->assertEquals(2, $models[1]->getId()->toScalar());
    }

    public function testFindWithFilterSortPaginate()
    {
        /** @var Model[]|array $models */
        $models = $this->repo->findWithFilterSortPaginate($this->fsp);
        $this->assertCount(5, $models);
    }

    public function testCountByFilterSortPaginate()
    {
        $this->assertEquals(7, $this->repo->countByFilterSortPaginate($this->fsp));
    }

    public function testInsert()
    {
        $model_1 = new Model(1001, 'Model name');
        $model_1->Array = ['q', 'w', 'e', 'r', 't', 'y' => [123]];
        $model_1->Nested_must = new NestedModel();
        $this->repo->insert($model_1);
        $this->repo->freeUpMemory();
        $foundModel = $this->repo->findById($model_1->getId()->toScalar());
        $this->assertEquals($model_1, $foundModel);


        $model_2 = new Model(1002, 'Model name');
        $model_2->Array = ['q', 'w', 'e', 'r', 't', 'y' => [123]];
        $model_2->Money = new Money(100, 'RUB');
        $model_2->Balances = [
            new Money(100, 'RUB'),
            new Money(100, 'RUB'),
            new Money(100, 'RUB'),
        ];
        $model_2->Nested_must = new NestedModel();


        $model_3 = new Model(1003, 'Model name');
        $model_3->Array = ['q', 'w', 'e', 'r', 't', 'y' => [123]];
        $model_3->Money = new Money(100, 'RUB');
        $model_3->Balances = [
            new Money(100, 'RUB'),
            new Money(100, 'RUB'),
            new Money(100, 'RUB'),
        ];
        $model_3->Nested_must = new NestedModel();


        $model_4 = new Model(1004, 'Model name');
        $model_4->Array = ['q', 'w', 'e', 'r', 't', 'y' => [123]];
        $model_4->Money = new Money(100, 'RUB');
        $model_4->Balances = [
            new Money(100, 'RUB'),
            new Money(100, 'RUB'),
            new Money(100, 'RUB'),
        ];
        $model_4->Nested = new NestedModel();
        $model_4->Nested->Money = new Money(777, 'EUR');
        $model_4->Nested_must = new NestedModel();


        $model_5 = new Model(1005, 'Model name');
        $model_5->Array = ['q', 'w', 'e', 'r', 't', 'y' => [123]];
        $model_5->Money = new Money(100, 'RUB');
        $model_5->Balances = [
            new Money(100, 'RUB'),
            new Money(100, 'RUB'),
            new Money(100, 'RUB'),
        ];
        $model_5->Nested = new NestedModel();
        $model_5->Nested->Money = new Money(777, 'EUR');
        $model_5->Nested->Array = [7, 7, 7];
        $model_5->Nested_must = new NestedModel();

        $this->repo->insert($model_1);
        $this->repo->insert($model_2);
        $this->repo->insert($model_3);
        $this->repo->insert($model_4);
        $this->repo->insert($model_5);
        $this->repo->freeUpMemory();

        $foundModel_1 = $this->repo->findById($model_1->getId()->toScalar());
        $foundModel_2 = $this->repo->findById($model_2->getId()->toScalar());
        $foundModel_3 = $this->repo->findById($model_3->getId()->toScalar());
        $foundModel_4 = $this->repo->findById($model_4->getId()->toScalar());
        $foundModel_5 = $this->repo->findById($model_5->getId()->toScalar());

        $this->assertEquals($model_1, $foundModel_1);
        $this->assertEquals($model_2, $foundModel_2);
        $this->assertEquals($model_3, $foundModel_3);
        $this->assertEquals($model_4, $foundModel_4);
        $this->assertEquals($model_5, $foundModel_5);
    }

    public function testInsertNestedMust()
    {
        $model_1 = new Model(1003, 'Model name');
        $model_1->Array = ['q', 'w', 'e', 'r', 't', 'y' => [123]];
        $this->expectException(ExtractorException::class);
        $this->repo->insert($model_1);
    }

    public function testInsertArrayMust()
    {
        $model_1 = new Model(1003, 'Model name');
        $this->expectException(ExtractorException::class);
        $this->repo->insert($model_1);
    }

    public function testUpdate()
    {
        /** @var Model $model */
        $model = $this->repo->findById(1);
        $model->name = 'first record';

        $this->repo->update($model);
        $this->repo->freeUpMemory();

        $foundModel = $this->repo->findById($model->getId()->toScalar());
        $this->assertEquals($model, $foundModel);
    }

    public function testSave()
    {
        /** @var Model $model_1 */
        $model_1 = $this->repo->findById(1);
        $model_1->name = '1 - first';

        $model_2 = new Model();
        $model_2->Array = ['q', 'w', 'e', 'r', 't', 'y' => [123]];
        $model_2->name = 'new model';
        $model_2->Nested = new NestedModel();
        $model_2->Nested->Money = new Money(100, 'RUB');
        $model_2->Nested->Array = [1, 2, 3];
        $model_2->Nested_must = new NestedModel();

        $this->repo->save($model_1);
        $this->repo->save($model_2);

        $this->repo->freeUpMemory();

        $this->assertEquals($model_1, $this->repo->findById($model_1));
        $this->assertEquals($model_2, $this->repo->findById($model_2));
    }

    public function testDelete()
    {
        /** @var Model $model */
        $model = $this->repo->findById(1);

        $this->repo->delete($model);
        $this->repo->freeUpMemory();

        $this->assertNull($this->repo->findById(1));
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
                    'money___amount' => 100,
                    'money___currency' => 'RUB',
                    'nested___money___amount' => 1000,
                    'nested___money___currency' => 'USD',
                    'nested___array' => json_encode([
                        'one' => 11,
                        'two' => 22,
                        'array' => [11, 22, 33]
                    ]),
                    'nested_must___money___amount' => 7777,
                    'nested_must___money___currency' => 'UAH',
                    'nested_must___array' => json_encode([
                        'one' => 111,
                        'two' => 222,
                        'array' => [111, 222, 333]
                    ]),
                ],
                [
                    'id' => 2,
                    'name' => 'second',
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
                    'money___amount' => 200,
                    'money___currency' => 'RUB',
                    'nested_must___money___amount' => 7777,
                    'nested_must___money___currency' => 'UAH',
                    'nested_must___array' => json_encode([
                        'one' => 111,
                        'two' => 222,
                        'array' => [111, 222, 333]
                    ]),
                ],
                [
                    'id' => 3,
                    'name' => 'third',
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
                    'money___amount' => 300,
                    'money___currency' => 'RUB',
                    'nested_must___money___amount' => 7777,
                    'nested_must___money___currency' => 'UAH',
                    'nested_must___array' => json_encode([
                        'one' => 111,
                        'two' => 222,
                        'array' => [111, 222, 333]
                    ]),
                ],
                [
                    'id' => 4,
                    'name' => 'forth',
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
                    'money___amount' => 400,
                    'money___currency' => 'RUB',
                    'nested_must___money___amount' => 7777,
                    'nested_must___money___currency' => 'UAH',
                    'nested_must___array' => json_encode([
                        'one' => 111,
                        'two' => 222,
                        'array' => [111, 222, 333]
                    ]),
                ],
                [
                    'id' => 5,
                    'name' => 'fifth',
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
                    'money___amount' => 500,
                    'money___currency' => 'RUB',
                    'nested_must___money___amount' => 7777,
                    'nested_must___money___currency' => 'UAH',
                    'nested_must___array' => json_encode([
                        'one' => 111,
                        'two' => 222,
                        'array' => [111, 222, 333]
                    ]),
                ],
                [
                    'id' => 6,
                    'name' => 'sixth',
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
                    'money___amount' => 600,
                    'money___currency' => 'RUB',
                    'nested_must___money___amount' => 7777,
                    'nested_must___money___currency' => 'UAH',
                    'nested_must___array' => json_encode([
                        'one' => 111,
                        'two' => 222,
                        'array' => [111, 222, 333]
                    ]),
                ],
                [
                    'id' => 7,
                    'name' => 'seventh',
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
                    'money___amount' => 700,
                    'money___currency' => 'RUB',
                    'nested_must___money___amount' => 7777,
                    'nested_must___money___currency' => 'UAH',
                    'nested_must___array' => json_encode([
                        'one' => 111,
                        'two' => 222,
                        'array' => [111, 222, 333]
                    ]),
                ],
                [
                    'id' => 8,
                    'name' => 'eighth',
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
                    'money___amount' => 800,
                    'money___currency' => 'RUB',
                    'nested_must___money___amount' => 7777,
                    'nested_must___money___currency' => 'UAH',
                    'nested_must___array' => json_encode([
                        'one' => 111,
                        'two' => 222,
                        'array' => [111, 222, 333]
                    ]),
                ],
                [
                    'id' => 9,
                    'name' => 'ninth',
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
                    'money___amount' => 900,
                    'money___currency' => 'RUB',
                    'nested_must___money___amount' => 7777,
                    'nested_must___money___currency' => 'UAH',
                    'nested_must___array' => json_encode([
                        'one' => 111,
                        'two' => 222,
                        'array' => [111, 222, 333]
                    ]),
                ],
                [
                    'id' => 10,
                    'name' => 'tenth',
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
                    'money___amount' => 000,
                    'money___currency' => 'RUB',
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
