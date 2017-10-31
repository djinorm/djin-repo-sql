<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 31.10.2017 12:03
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\MySQL\Mappers;


use DateTimeInterface;
use DjinORM\Djin\Helpers\RepoHelper;
use DjinORM\Djin\Mappers\ScalarMapper;

class DatetimeMapper extends ScalarMapper
{

    /**
     * @var bool
     */
    private $isImmutable;

    public function __construct($modelProperty, $dbColumn = null, $allowNull = false, $isImmutable = true)
    {
        parent::__construct($modelProperty, $dbColumn, $allowNull);
        $this->isImmutable = $isImmutable;
    }

    /**
     * @param array $data
     * @param object $object
     * @return DateTimeInterface|null
     * @throws \DjinORM\Djin\Exceptions\HydratorException
     */
    public function hydrate(array $data, $object): ?DateTimeInterface
    {
        $column = $this->getDbColumn();
        if (!isset($data[$column])) {
            if ($this->isAllowNull()) {
                RepoHelper::setProperty($object, $this->getModelProperty(), null);
                return null;
            }
            throw $this->nullHydratorException($this->getClassName(), $object);
        }

        $class = $this->getClassName();
        $datetime = new $class($data[$column]);
        RepoHelper::setProperty($object, $this->getModelProperty(), $datetime);
        return $datetime;
    }

    public function extract($object): array
    {
        /** @var DateTimeInterface $datetime */
        $datetime = RepoHelper::getProperty($object, $this->getModelProperty());

        if ($datetime === null) {
            if ($this->isAllowNull() == false) {
                throw $this->nullExtractorException($this->getClassName(), $object);
            }
            return [
                $this->getDbColumn() => null
            ];
        }

        return [
            $this->getDbColumn() => $datetime->format('Y-m-d H:i:s')
        ];
    }

    public function getFixtures(): array
    {
        $fixtures = [
            '2001-01-01 01:01:01',
            '2002-02-02 02:02:02',
            '2003-03-03 03:03:03',
            '2004-04-04 04:04:04',
            '2005-05-05 05:05:05',
        ];

        if ($this->isAllowNull()) {
            $fixtures[] = null;
        }

        return $fixtures;
    }

    protected function getClassName(): string
    {
        return $this->isImmutable ? \DateTimeImmutable::class : \DateTime::class;
    }
}