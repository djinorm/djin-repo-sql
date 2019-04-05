<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 17.07.2018 18:01
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\Fakes;


use Aura\SqlQuery\QueryInterface;
use DjinORM\Djin\Mappers\ArrayMapper;
use DjinORM\Djin\Mappers\IdMapper;
use DjinORM\Djin\Mappers\IntMapper;
use DjinORM\Djin\Mappers\NestedArrayMapper;
use DjinORM\Djin\Mappers\StringMapper;
use DjinORM\Djin\Mappers\NestedMapper;
use DjinORM\Repositories\Sql\MappedSqlRepository;
use PDOStatement;

class MappedSqlRepo extends MappedSqlRepository
{

    protected $queryCount = 0;

    /** @var QueryInterface */
    public $lastQuery;

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
        $this->lastQuery = $query;
        $this->queryCount++;
        return parent::getStatement($query);
    }

    protected function map(): array
    {
        return [
            new IdMapper('id'),
            new StringMapper('name'),
            new ArrayMapper('Array', false),
            new NestedMapper(
                'Money',
                Money::class,
                [
                    new IntMapper('Amount', false),
                    new StringMapper('Currency', null, false),
                ],
                true
            ),
            new NestedArrayMapper(
                'Balances',
                Money::class,
                [
                    new IntMapper('Amount', false),
                    new StringMapper('Currency', null, false),
                ],
                true
            ),
            new NestedMapper(
                'Nested',
                NestedModel::class,
                [
                    new NestedMapper(
                        'Money',
                        Money::class,
                        [
                            new IntMapper('Amount', false),
                            new StringMapper('Currency', null, false),
                        ],
                        true
                    ),
                    new ArrayMapper('Array', true),
                ],
                true
            ),
            new NestedMapper(
                'Nested_must',
                NestedModel::class,
                [
                    new NestedMapper(
                        'Money',
                        Money::class,
                        [
                            new IntMapper('Amount', false),
                            new StringMapper('Currency', null, false),
                        ],
                        true
                    ),
                    new ArrayMapper('Array', true),
                ],
                false
            ),
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