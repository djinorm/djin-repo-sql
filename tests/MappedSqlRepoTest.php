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
                    'Array' => json_encode([
                        'one' => 1,
                        'two' => 2,
                        'array' => [1, 2, 3]
                    ]),
                    'Balances' => json_encode([
                        [
                            'Amount' => 111,
                            'Currency' => 'USD'
                        ],
                        [
                            'Amount' => 222,
                            'Currency' => 'RUB'
                        ],
                    ]),
                    'Money' => 1,
                    'Money___Amount' => 100,
                    'Money___Currency' => 'RUB',
                    'Nested' => 1,
                    'Nested___Money' => 1,
                    'Nested___Money___Amount' => 1000,
                    'Nested___Money___Currency' => 'USD',
                    'Nested___Array' => json_encode([
                        'one' => 11,
                        'two' => 22,
                        'array' => [11, 22, 33]
                    ]),
                    'Nested_must' => 1,
                    'Nested_must___Money' => 1,
                    'Nested_must___Money___Amount' => 7777,
                    'Nested_must___Money___Currency' => 'UAH',
                    'Nested_must___Array' => json_encode([
                        'one' => 111,
                        'two' => 222,
                        'array' => [111, 222, 333]
                    ]),
                ],
            ],
        ]);
    }

}
