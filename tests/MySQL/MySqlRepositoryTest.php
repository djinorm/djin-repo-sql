<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 30.09.2017 21:10
 * @author Timur Kasumov (aka XAKEPEHOK)
 */

namespace DjinORM\Repositories\Sql\MySQL;

use DjinORM\Djin\Mappers\IdMapper;
use DjinORM\Djin\Mappers\IntMapper;
use DjinORM\Djin\Mappers\MapperInterface;
use DjinORM\Djin\Mappers\StringMapper;
use DjinORM\Djin\Model\ModelInterface;
use DjinORM\Djin\Model\ModelTrait;
use PDO;
use PHPUnit\DbUnit\DataSet\ArrayDataSet;
use PHPUnit\DbUnit\TestCase;

class MySqlRepositoryTest extends TestCase
{

    const TEST_TABLE_NAME = '__repository_test';

    /** @var MySqlRepository */
    private $repo;

    /** @var PDO */
    private static $pdo;

    private $connection;

    protected function getPdo(): PDO
    {
        if (self::$pdo == null) {
            self::$pdo = new PDO($GLOBALS['MYSQL_DB_DSN'], $GLOBALS['MYSQL_DB_USER'], $GLOBALS['MYSQL_DB_PASSWD']);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return self::$pdo;
    }

    public function getConnection()
    {
        if ($this->connection === null) {
            $this->connection = $this->createDefaultDBConnection(
                $this->getPdo(),
                $GLOBALS['MYSQL_DB_DBNAME']
            );
        }
        return $this->connection;
    }


    public function testFindById()
    {
        $model = $this->repo->findById(1);
        $this->assertInstanceOf(get_class($this->getNewModelInstance()), $model);
        $this->assertEquals(1, $model->getId()->toScalar());
        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals('first', $model->name);

        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals(1, $this->repo->whereFiltered);

        $model = $this->repo->findById(1);
        $this->assertInstanceOf(get_class($this->getNewModelInstance()), $model);
        $this->assertEquals(1, $model->getId()->toScalar());
        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals('first', $model->name);

        $this->assertEquals(1, $this->repo->getQueryCount());
    }

    public function testFindByIdNotFound()
    {
        $this->assertNull($this->repo->findById(777));
    }

    public function testFindByIn()
    {
        $models = $this->repo->findByIn('name', ['first', 'fourth']);
        $this->assertCount(2, $models);
        $this->assertEquals(1, $models[0]->getId()->toScalar());
        $this->assertEquals(4, $models[1]->getId()->toScalar());

        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals(1, $this->repo->whereFiltered);
    }

    public function testFindByIds()
    {
        $models = $this->repo->findByIds([2,3]);
        $this->assertCount(2, $models);
        $this->assertEquals('second', $models[0]->name);
        $this->assertEquals('third', $models[1]->name);

        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals(1, $this->repo->whereFiltered);
    }

    public function testFindOneByCondition()
    {
        $model = $this->repo->findOneByCondition([
            'id' => 2,
            'name' => 'second'
        ]);
        $this->assertEquals(2, $model->getId()->toScalar());

        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals('second', $model->name);

        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals(1, $this->repo->whereFiltered);

        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertNull($this->repo->findOneByCondition(['id' => 777]));
    }

    public function testFindByCondition()
    {
        $models = $this->repo->findByCondition([
            'group_1' => 'gr2',
            'group_2' => 'gr3',
        ]);

        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals(1, $this->repo->whereFiltered);

        $this->assertCount(2, $models);
        $this->assertEquals(3, $models[0]->getId()->toScalar());
        $this->assertEquals(4, $models[1]->getId()->toScalar());
    }

    public function testCountByCondition()
    {
        $this->assertEquals(3, $this->repo->countByCondition([
            'group_2' => 'gr3',
        ]));

        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals(1, $this->repo->whereFiltered);
    }

    public function testFindAll()
    {
        $models = $this->repo->findAll();
        $this->assertCount(4, $models);
        $this->assertEquals(1, $models[0]->getId()->toScalar());
        $this->assertEquals(2, $models[1]->getId()->toScalar());
        $this->assertEquals(3, $models[2]->getId()->toScalar());
        $this->assertEquals(4, $models[3]->getId()->toScalar());

        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals(1, $this->repo->whereFiltered);
    }

    public function testCountAll()
    {
        $this->assertEquals(4, $this->repo->countAll());

        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals(1, $this->repo->whereFiltered);
    }

    public function testExists()
    {
        $this->assertTrue($this->repo->exists(['id' => 1]));
        $this->assertFalse($this->repo->exists(['id' => 10]));

        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals(2, $this->repo->whereFiltered);
    }

    public function testInsert()
    {
        $model = $this->getNewModelInstance();
        $model->getId()->setPermanentId(5);
        $model->name = 'fifth';
        $model->group_1 = 'gr5';
        $model->group_2 = 'gr6';
        $model->increment = 5;
        $model->not_increment = 5;
        $this->repo->insert($model);

        $findModel = $this->repo->findById(5);
        $this->assertEquals($model, $findModel);
    }

    public function testUpdate()
    {
        $model_1 = $this->repo->findById(1);
        $model_2 = $this->repo->findById(2);

        $model_1->name = 'qwerty';
        $model_1->group_1 = 'gr7';
        $model_1->increment = 1;
        $model_1->not_increment = 1;
        $this->repo->update($model_1);

        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals(3, $this->repo->whereFiltered);

        $repo = $this->createRepo();

        $findModel_1 = $repo->findById(1);
        $findModel_2 = $repo->findById(2);

        $this->assertEquals($model_1->getId()->toScalar(), $findModel_1->getId()->toScalar());
        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals($model_1->name, $findModel_1->name);

        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals($model_1->group_1, $findModel_1->group_1);

        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals($model_1->group_2, $findModel_1->group_2);

        $this->assertEquals(2, $findModel_1->increment);

        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals($model_1->not_increment, $findModel_1->not_increment);

        $this->assertEquals($model_2, $findModel_2);
    }

    public function testDelete()
    {
        $model = $this->repo->findById(1);
        $this->repo->delete($model);

        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertEquals(2, $this->repo->whereFiltered);

        $repo = $this->createRepo();
        $this->assertEquals(3, $repo->countAll());
    }

    public function testIsTransactional()
    {
        $this->assertEquals('boolean', gettype($this->repo->isTransactional()));
    }

    ###############################################################################################

    public function setUp()
    {
        $this->createTempTable();
        parent::setUp();
        $this->repo = $this->createRepo();
    }

    public function tearDown()
    {
        $this->dropTempTable();
        parent::tearDown();
    }

    protected function getDataSet(): ArrayDataSet
    {
        return new ArrayDataSet([
            self::TEST_TABLE_NAME => [
                [
                    'id' => 1,
                    'name' => 'first',
                    'group_1' => 'gr1',
                    'group_2' => 'gr2',
                    'increment' => 1,
                    'not_increment' => 1,
                ],
                [
                    'id' => 2,
                    'name' => 'second',
                    'group_1' => 'gr1',
                    'group_2' => 'gr3',
                    'increment' => 2,
                    'not_increment' => 2,
                ],
                [
                    'id' => 3,
                    'name' => 'third',
                    'group_1' => 'gr2',
                    'group_2' => 'gr3',
                    'increment' => 3,
                    'not_increment' => 3,
                ],
                [
                    'id' => 4,
                    'name' => 'fourth',
                    'group_1' => 'gr2',
                    'group_2' => 'gr3',
                    'increment' => 4,
                    'not_increment' => 4,
                ],
            ],
        ]);
    }

    /**
     * @return ModelInterface
     */
    private function getNewModelInstance()
    {
        /** @noinspection PhpUndefinedVariableInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $class = get_class($this->repo::$modelExample);
        return new $class;
    }

    private function createTempTable()
    {
        $pdo = $this->getPdo();
        $sql = "DROP TABLE IF EXISTS `" . self::TEST_TABLE_NAME . "`";
        $pdo->exec($sql);
        $sql = "
           CREATE TABLE `" . self::TEST_TABLE_NAME . "` (
             `id` INT,
             `name` VARCHAR(40),
             `group_1` VARCHAR(40),
             `group_2` VARCHAR(40),
             `increment` INT,
             `not_increment` INT
           );
        ";

        $pdo->exec($sql);
    }

    private function dropTempTable()
    {
        $pdo = $this->getPdo();
        $statement = $pdo->prepare("DROP TABLE IF EXISTS  " . self::TEST_TABLE_NAME);
        $statement->execute();
    }

    private function createRepo()
    {
        /** @noinspection PhpUndefinedClassConstantInspection */
        return new class($this->getPDO(), self::TEST_TABLE_NAME) extends MySqlRepository {

            private $tableName;

            public $whereFiltered = 0;

            public static $modelExample;

            public function __construct(PDO $pdo, string $tableName)
            {
                parent::__construct($pdo);
                self::$modelExample = new class() implements ModelInterface {
                    use ModelTrait;
                    public $id;
                    public $name;
                    public $group_1;
                    public $group_2;
                    public $increment = 0;
                    public $not_increment = 0;
                };
                $this->tableName = $tableName;
            }

            public static function getModelClass(): string
            {
                return get_class(self::$modelExample);
            }

            protected function getTableName(): string
            {
                return $this->tableName;
            }

            protected function getIncrementNumericFields(): array
            {
                return ['increment'];
            }

            protected function whereFilter(string $sql): string
            {
                $this->whereFiltered++;
                return parent::whereFilter($sql);
            }

            /**
             * @return MapperInterface[]
             */
            protected function map(): array
            {
                return [
                    new IdMapper('id'),
                    new StringMapper('name'),
                    new StringMapper('group_1'),
                    new StringMapper('group_2'),
                    new IntMapper('increment'),
                    new IntMapper('not_increment'),
                ];
            }
        };
    }
}
