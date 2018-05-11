<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 10.05.2018 17:04
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\Fakes;


use Aura\SqlQuery\QueryInterface;
use DjinORM\Djin\Model\ModelInterface;
use DjinORM\Repositories\Sql\SqlRepository;
use PDOStatement;

class SimpleSqlRepo extends SqlRepository
{

    protected $queryCount = 0;

    public function findAll(): array
    {
        return $this->fetchAndPopulateMany($this->select());
    }

    public function freeUpMemory()
    {
        parent::freeUpMemory();
        $this->queryCount = 0;
    }

    /**
     * @return int
     */
    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    protected function getStatement(QueryInterface $query): PDOStatement
    {
        $this->queryCount++;
        return parent::getStatement($query);
    }

    /**
     * @param array $data
     * @return ModelInterface
     * @throws \DjinORM\Djin\Exceptions\InvalidArgumentException
     * @throws \DjinORM\Djin\Exceptions\LogicException
     */
    protected function hydrate(array $data): ModelInterface
    {
        $model = new Model();
        $model->id->setPermanentId($data['id']);
        $model->name = $data['name'];
        return $model;
    }

    /**
     * @param Model|ModelInterface $model
     * @return array
     */
    protected function extract(ModelInterface $model): array
    {
        return [
            'id' => $model->id->toScalar(),
            'name' => $model->name
        ];
    }

    protected function getTableName(): string
    {
        return 'djin-repo';
    }

    public static function getModelClass(): string
    {
        return Model::class;
    }
}