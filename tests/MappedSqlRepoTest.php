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
use DjinORM\Djin\Id\MemoryIdGenerator;
use DjinORM\Repositories\Sql\Components\DbTestCase;
use DjinORM\Repositories\Sql\Fakes\MappedSqlRepo;
use DjinORM\Repositories\Sql\Fakes\Model;
use DjinORM\Repositories\Sql\Fakes\Money;
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
        $model = new Model(1000, 'Model name');
        $model->Array = ['q', 'w', 'e', 'r', 't', 'y' => [123]];
        $model->Money = new Money(100, 'RUB');
        $model->Balances = [
            new Money(100, 'RUB'),
            new Money(100, 'RUB'),
            new Money(100, 'RUB'),
        ];

        $this->repo->insert($model);
        $this->repo->freeUpMemory();

        $foundModel = $this->repo->findById($model->getId()->toScalar());
        $this->assertEquals($model, $foundModel);
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
        $model_2->name = 'new model';

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
                ],
            ],
        ]);
    }

}
