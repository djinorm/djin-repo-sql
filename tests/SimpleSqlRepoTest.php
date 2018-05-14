<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 10.05.2018 16:48
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
use DjinORM\Repositories\Sql\Exceptions\PDOExceptionWithSql;
use DjinORM\Repositories\Sql\Fakes\Model;
use DjinORM\Repositories\Sql\Fakes\SimpleSqlRepo;
use PHPUnit\DbUnit\DataSet\ArrayDataSet;

class SimpleSqlRepoTest extends DbTestCase
{

    /** @var SimpleSqlRepo */
    private $repo;

    /** @var FilterSortPaginate */
    private $fsp;

    protected function setUp()
    {
        parent::setUp();
        $this->repo = new SimpleSqlRepo(
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

    public function testPdoExceptionWithSql()
    {
        $this->expectException(PDOExceptionWithSql::class);
        $model = new Model();
        $model->name = null;
        $this->repo->insert($model);
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
        $model = new Model();

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
                ],
                [
                    'id' => 2,
                    'name' => 'second',
                ],
                [
                    'id' => 3,
                    'name' => 'third',
                ],
                [
                    'id' => 4,
                    'name' => 'forth',
                ],
                [
                    'id' => 5,
                    'name' => 'fifth',
                ],
                [
                    'id' => 6,
                    'name' => 'sixth',
                ],
                [
                    'id' => 7,
                    'name' => 'seventh',
                ],
                [
                    'id' => 8,
                    'name' => 'eighth',
                ],
                [
                    'id' => 9,
                    'name' => 'ninth',
                ],
                [
                    'id' => 10,
                    'name' => 'tenth',
                ],
            ],
        ]);
    }
}
