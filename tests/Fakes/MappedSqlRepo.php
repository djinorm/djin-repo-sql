<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 17.07.2018 18:01
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\Fakes;


use Aura\SqlQuery\QueryInterface;
use DjinORM\Djin\Mappers\ArrayMapper;
use DjinORM\Djin\Mappers\Handler\MappersHandler;
use DjinORM\Djin\Mappers\IdMapper;
use DjinORM\Djin\Mappers\IntMapper;
use DjinORM\Djin\Mappers\StringMapper;
use DjinORM\Djin\Mappers\SubclassMapper;
use DjinORM\Repositories\Sql\MapperSqlRepository;
use PDOStatement;

class MappedSqlRepo extends MapperSqlRepository
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

    protected function map(): array
    {
        return [
            new IdMapper('id'),
            new StringMapper('name'),
            new ArrayMapper('Array', 'array', true),
            new SubclassMapper(
                'Money',
                'money',
                new MappersHandler(Money::class, [
                    new IntMapper('Amount', 'amount'),
                    new StringMapper('Currency', 'currency'),
                ]),
                true
            ),
            new ArrayMapper('Balances', 'balances', true, new MappersHandler(Money::class, [
                new IntMapper('Amount', 'amount'),
                new StringMapper('Currency', 'currency'),
            ])),
            new SubclassMapper(
                'Nested',
                'nested',
                new MappersHandler(Money::class, [
                    new SubclassMapper(
                        'Money',
                        'money',
                        new MappersHandler(Money::class, [
                            new IntMapper('Amount', 'amount'),
                            new StringMapper('Currency', 'currency'),
                        ]),
                        true
                    ),
                    new ArrayMapper('Array', 'array', true),
                ]),
                true
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