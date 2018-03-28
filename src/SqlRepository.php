<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 19.04.2017 2:39
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql;


use Aura\Sql\ExtendedPdo;
use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\QueryFactory;
use Aura\SqlQuery\QueryInterface;
use DjinORM\Djin\Helpers\DjinHelper;
use DjinORM\Djin\Id\Id;
use DjinORM\Djin\Id\IdGeneratorInterface;
use DjinORM\Djin\Model\ModelInterface;
use DjinORM\Djin\Repository\RepositoryInterface;
use DjinORM\Repositories\Sql\Exceptions\PDOExceptionWithSql;
use PDOStatement;

abstract class SqlRepository implements RepositoryInterface
{

    /** @var IdGeneratorInterface */
    protected $idGenerator;
    /**
     * @var ExtendedPdo
     */
    protected $pdo;
    /**
     * @var QueryFactory
     */
    protected $builder;

    /** @var ModelInterface[] */
    protected $models;

    /**
     * Repository constructor.
     * @param ExtendedPdo $pdo
     * @param QueryFactory $factory
     * @param IdGeneratorInterface $idGenerator
     */
    public function __construct(ExtendedPdo $pdo, QueryFactory $factory, IdGeneratorInterface $idGenerator)
    {
        $this->idGenerator = $idGenerator;
        $this->pdo = $pdo;
        $this->builder = $factory;
    }

    /**
     * @param $id
     * @return ModelInterface|null
     * @throws \DjinORM\Djin\Exceptions\InvalidArgumentException
     * @throws \DjinORM\Djin\Exceptions\MismatchModelException
     * @throws \DjinORM\Djin\Exceptions\NotPermanentIdException
     */
    public function findById($id): ?ModelInterface
    {
        $select = $this->select()->where('id = :id');
        $select->bindValue('id', DjinHelper::getScalarId($id));
        return $this->fetchAndPopulateOne($select);
    }

    /**
     * @param ModelInterface $model
     * @return mixed|void
     * @throws \DjinORM\Djin\Exceptions\InvalidArgumentException
     * @throws \DjinORM\Djin\Exceptions\LogicException
     */
    public function save(ModelInterface $model)
    {
        if (isset($this->models[$model->getId()->toScalar()])) {
            $this->update($model);
        } else {
            $this->insert($model);
        }
    }

    /**
     * @param ModelInterface $model
     * @return mixed|void
     * @throws \DjinORM\Djin\Exceptions\InvalidArgumentException
     * @throws \DjinORM\Djin\Exceptions\LogicException
     */
    public function insert(ModelInterface $model)
    {
        $this->setPermanentId($model);
        $data = $this->extract($model);
        $insert = $this->builder->newInsert()->into($this->getTableName());
        $insert->cols($data);
        $this->insertStatement($insert);
    }

    /**
     * @param ModelInterface $model
     * @return mixed|void
     */
    public function update(ModelInterface $model)
    {
        $data = $this->extract($model);
        $update = $this->builder->newUpdate()->table($this->getTableName());
        $update->cols($data);
        $update->where("{$this->getIdName()} = :id")->bindValue('id', $model->getId()->toScalar());
        $this->updateStatement($update);
    }

    /**
     * @param ModelInterface $model
     * @return mixed|void
     */
    public function delete(ModelInterface $model)
    {
        $delete = $this->builder->newDelete()->from($this->getTableName());
        $delete->where("{$this->getIdName()} = :id")->bindValue('id', $model->getId()->toScalar());
        $this->deleteStatement($delete);
        unset($this->models[$model->getId()->toScalar()]);
    }


    ## Djin repo interface ##


    /**
     * @param ModelInterface $model
     * @return Id
     * @throws \DjinORM\Djin\Exceptions\InvalidArgumentException
     * @throws \DjinORM\Djin\Exceptions\LogicException
     */
    public function setPermanentId(ModelInterface $model): Id
    {
        if (!$model->getId()->isPermanent()) {
            $model->getId()->setPermanentId($this->idGenerator->getNextId($model));
        }
        return $model->getId();
    }

    /**
     * Освобождает из памяти загруженные модели.
     * ВНИМАНИЕ: после освобождения памяти в случае сохранения существующей модели через self::save()
     * в БД будет вставлена новая запись вместо обновления существующей
     * @return mixed|void
     */
    public function freeUpMemory()
    {
        $this->models = [];
    }


    ## PDOStatement ##


    protected function selectStatement(SelectInterface $select): PDOStatement
    {
        return $this->getStatement($select);
    }

    protected function insertStatement(InsertInterface $insert): PDOStatement
    {
        return $this->getStatement($insert);
    }

    protected function updateStatement(UpdateInterface $update): PDOStatement
    {
        return $this->getStatement($update);
    }

    protected function deleteStatement(DeleteInterface $delete): PDOStatement
    {
        return $this->getStatement($delete);
    }

    protected function getStatement(QueryInterface $query)
    {
        try {
            $stm = $this->pdo->perform($query->getStatement(), $query->getBindValues());
        } catch (\PDOException $exception) {
            throw new PDOExceptionWithSql($query->getStatement(), $query->getBindValues(), $exception);
        }
        return $stm;
    }


    ## Fetch ##


    /**
     * @param SelectInterface $select
     * @return array|bool
     */
    protected function fetchOne(SelectInterface $select)
    {
        return $this->selectStatement($select)->fetch();
    }

    /**
     * @param SelectInterface $select
     * @return array
     */
    protected function fetchMany(SelectInterface $select): array
    {
        return $this->selectStatement($select)->fetchAll();
    }


    ## Populate ##


    protected function populateOne($data): ?ModelInterface
    {
        if (!$data) {
            return null;
        }

        $model = $this->hydrate($data);
        $id = $model->getId()->toScalar();

        if (!isset($this->models[$id])) {
            $this->models[$id] = $model;
        } else {
            unset($model);
        }

        return $this->models[$id];
    }

    protected function populateMany($dataArray): array
    {
        $models = [];
        foreach ($dataArray as $data) {
            $models[] = $this->populateOne($data);
        }
        return $models;
    }


    ## Helpers ##


    protected function fetchAndPopulateOne(SelectInterface $select): ?ModelInterface
    {
        $data = $this->fetchOne($select);
        return $this->populateOne($data);
    }

    protected function fetchAndPopulateMany(SelectInterface $select): array
    {
        $dataArray = $this->fetchMany($select);
        return $this->populateMany($dataArray);
    }

    protected function select(array $cols = ['*']): SelectInterface
    {
        return $this->builder->newSelect()->cols($cols)->from($this->getTableName());
    }

    protected function getIdName(): string
    {
        /** @var ModelInterface $class */
        $class = $this->getModelClass();
        return $class::getModelIdPropertyName();
    }


    ## Hydrate & Extract ##


    abstract protected function hydrate(array $data): ModelInterface;

    abstract protected function extract(ModelInterface $model);


    ## Название таблицы и класса модели ##


    abstract protected function getTableName(): string;

    abstract public static function getModelClass(): string;


}