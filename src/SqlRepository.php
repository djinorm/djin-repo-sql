<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 19.04.2017 2:39
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql;

use DjinORM\Djin\Helpers\DjinHelper;
use DjinORM\Djin\Id\Id;
use DjinORM\Djin\Model\ModelInterface;
use DjinORM\Djin\Repository\MapperRepository;
use DjinORM\Djin\Repository\RepositoryInterface;
use PDO;
use PDOStatement;

abstract class SqlRepository extends MapperRepository implements RepositoryInterface
{

    /** @var PDO */
    protected $pdo;

    /**
     * Repository constructor.
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param ModelInterface|Id|int|string $id
     * @return ModelInterface|null
     * @throws \DjinORM\Djin\Exceptions\InvalidArgumentException
     * @throws \DjinORM\Djin\Exceptions\MismatchModelException
     * @throws \DjinORM\Djin\Exceptions\NotPermanentIdException
     */
    public function findById($id)
    {
        $scalarId = DjinHelper::getScalarId($id);

        if ($model = $this->loadedById($scalarId)) {
            return $model;
        }

        $sql = $this->buildSqlSelectQuery(null, $this->getIdName() . ' = :id', 1);
        $data = $this->prepareAndExecute($sql, [':id' => $scalarId])->fetch();

        $this->queryCount++;
        return $data ? $this->populate($data) : null;
    }

    /**
     * @param string $property
     * @param array $values
     * @param array $andCondition
     * @return ModelInterface[]
     */
    public function findByIn(string $property, array $values, array $andCondition = []): array
    {
        if (empty($values)) return [];

        $property = $this->filterColumnName($property);
        $bindings = str_repeat('?,', count($values) - 1) . '?';
        $sql = $this->buildSqlSelectQuery(null, "{$property} IN ({$bindings})");

        if (!empty($andCondition)) {
            $sql.= " AND " . $this->buildSqlWhereCondition($andCondition);
            $values = array_merge($values, $andCondition);
        }

        $dataArray = $this->prepareAndExecute($sql, array_values($values))->fetchAll();

        $this->queryCount++;
        return $this->populateArray($dataArray);
    }

    /**
     * @param array $ids
     * @return ModelInterface[]
     */
    public function findByIds(array $ids): array
    {
        return $this->findByIn($this->getIdName(), $ids);
    }

    /**
     * @param array $condition
     * @return ModelInterface|null
     */
    public function findOneByCondition(array $condition): ?ModelInterface
    {
        $sql = $this->buildSqlSelectQuery(null, $this->buildSqlWhereCondition($condition), 1);
        $data = $this->prepareAndExecute($sql, array_values($condition))->fetch();

        $this->queryCount++;
        return $data ? $this->populate($data) : null;
    }

    /**
     * @param array $condition
     * @return ModelInterface[]
     */
    public function findByCondition(array $condition): array
    {
        $sql = $this->buildSqlSelectQuery(null, $this->buildSqlWhereCondition($condition));

        $dataArray = $this->prepareAndExecute($sql, array_values($condition))->fetchAll();
        $this->queryCount++;
        return $this->populateArray($dataArray);
    }

    public function countByCondition(array $condition): int
    {
        $sql = $this->buildSqlSelectQuery("COUNT(*)", $this->buildSqlWhereCondition($condition));
        $this->queryCount++;
        return (int) $this->prepareAndExecute($sql, array_values($condition))->fetchColumn();
    }

    public function findOneBySql(string $sqlWhere, array $params = []): ?ModelInterface
    {
        $sql = $this->buildSqlSelectQuery(null, $sqlWhere, 1);
        $data = $this->prepareAndExecute($sql, $params)->fetch();
        $this->queryCount++;
        return $data ? $this->populate($data) : null;
    }

    /**
     * @param string $sqlWhere
     * @param array $params
     * @return ModelInterface[]
     */
    public function findBySql(string $sqlWhere, array $params = []): array
    {
        $sql = $this->buildSqlSelectQuery(null, $sqlWhere);
        $dataArray = $this->prepareAndExecute($sql, $params)->fetchAll();
        $this->queryCount++;
        return $this->populateArray($dataArray);
    }

    /**
     * @param string $sqlWhere
     * @param array $params
     * @return int
     */
    public function countBySql(string $sqlWhere, array $params = []): int
    {
        $sql = $this->buildSqlSelectQuery("COUNT(*)", $sqlWhere);
        $this->queryCount++;
        return (int) $this->prepareAndExecute($sql, $params)->fetchColumn();
    }

    /**
     * @return ModelInterface[]
     */
    public function findAll(): array
    {
        $sql = $this->buildSqlSelectQuery();
        $dataArray = $this->prepareAndExecute($sql)->fetchAll();
        $this->queryCount++;
        return $this->populateArray($dataArray);
    }

    public function countAll(): int
    {
        $sql = $sql = $this->buildSqlSelectQuery("COUNT(*)");
        return (int) $this->prepareAndExecute($sql)->fetchColumn();
    }

    public function exists(array $condition): bool
    {
        $sql = $this->buildSqlSelectQuery("1", $this->buildSqlWhereCondition($condition), 1);
        $this->queryCount++;
        return $this->prepareAndExecute($sql, array_values($condition))->fetch() ? true : false;
    }

    public function insert(ModelInterface $model)
    {
        $data = $this->extract($model);

        $table = static::getTableName();
        $sql = "INSERT INTO {$table} " . $this->buildSqlInsertCondition($data);
        $this->prepareAndExecute($sql, array_values($data));

        $this->queryCount++;
    }

    public function update(ModelInterface $model)
    {
        $data = $this->getDiffDataForUpdate($model);
        if (!empty($data)) {
            $table = static::getTableName();

            $incrementFields = array_map(function ($property) {
                return $this->filterColumnName($property);
            }, $this->getIncrementNumericFields());

            $sqlUpdate = $this->buildSqlUpdateCondition($data, $incrementFields);
            $sqlWhere = $this->whereFilter($this->getIdName() . ' = ?');
            $sql = "UPDATE {$table} SET {$sqlUpdate} WHERE {$sqlWhere}";
            $bindings = array_values($data);
            $bindings[] = $model->getId()->toScalar();
            $this->prepareAndExecute($sql, $bindings);
            $this->queryCount++;
        }
    }

    public function delete(ModelInterface $model)
    {
        $table = static::getTableName();
        $sqlWhere = $this->whereFilter($this->getIdName() . ' = :id');
        $sql = "DELETE FROM {$table} WHERE {$sqlWhere}";
        $this->prepareAndExecute($sql, [':id' => $model->getId()->toScalar()]);

        unset($this->models[$model->getId()->toScalar()]);
        unset($this->rawData[$model->getId()->toScalar()]);
        unset($model);
    }

    abstract protected function getTableName(): string;

    protected function getIncrementNumericFields(): array
    {
        return [];
    }

    protected function filterColumnName($property): string
    {
        return preg_replace('~[^\w]~i', '', $property);
    }

    protected function getDiffDataForUpdate(ModelInterface $model): array
    {
        $extracted = $this->extract($model);
        $data = array_diff_assoc($extracted, $this->rawData[$model->getId()->toScalar()]);

        foreach ($this->getIncrementNumericFields() as $field) {
            $data[$field] = $extracted[$field];
        }

        return $data;
    }

    protected function getIdName(): string
    {
        /** @var ModelInterface $class */
        $class = $this->getModelClass();
        return $this->filterColumnName($class::getModelIdPropertyName());
    }

    protected function getSelectSql(): string
    {
        return '*';
    }
    
    protected function whereFilter(string $sql): string
    {
        return $sql;
    }

    protected function buildSqlWhereCondition(array $array, $operator = 'AND'): string
    {
        $condition = '';
        foreach (array_keys($array) as $property) {
            $property = $this->filterColumnName($property);
            $condition.= "{$property} = ? {$operator} ";
        }
        return mb_substr($condition, 0, (strlen($operator)+1) * -1);
    }

    protected function buildSqlInsertCondition(array $array): string
    {
        $sqlColumns = '';
        foreach (array_keys($array) as $property) {
            $property = $this->filterColumnName($property);
            $sqlColumns.= "{$property}, ";
        }
        $sqlColumns = mb_substr($sqlColumns, 0, -2);

        $sqlValues = str_repeat('?,', count($array) - 1) . '?';

        return "({$sqlColumns}) VALUES ({$sqlValues})";
    }

    protected function buildSqlUpdateCondition(array $array, array $increment = []): string
    {
        $condition = '';
        foreach (array_keys($array) as $property) {
            $property = $this->filterColumnName($property);

            if (in_array($property, $increment, false)) {
                $condition.= "{$property} = {$property} + ?, ";
            } else {
                $condition.= "{$property} = ?, ";
            }
        }
        return mb_substr($condition, 0, -2);
    }

    protected function buildSqlSelectQuery(string $select = null, string $where = '', int $limit = null)
    {
        $table = $this->getTableName();

        if ($select === null) {
            $select = $this->getSelectSql();
        }

        $sql = "SELECT {$select} FROM {$table}";

        $where = trim($this->whereFilter($where));
        if (!empty($where)) {
            $sql.= " WHERE {$where}";
        }

        if ($limit > 0) {
            $sql.= " LIMIT {$limit}";
        }

        return $sql;
    }

    protected function prepareAndExecute(string $sql, $params = []): PDOStatement
    {
        $stm = $this->pdo->prepare($sql);
        $stm->execute($params);
        return $stm;
    }

}