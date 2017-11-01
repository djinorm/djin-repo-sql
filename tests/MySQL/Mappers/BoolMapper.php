<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 01.11.2017 17:02
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\MySQL\Mappers;


use DjinORM\Djin\Helpers\RepoHelper;
use DjinORM\Djin\Mappers\ScalarMapper;

class BoolMapper extends ScalarMapper
{

    public function __construct($modelProperty, $dbColumn = null)
    {
        parent::__construct($modelProperty, $dbColumn, true);
    }

    /**
     * @param array $data
     * @param object $object
     * @return bool
     */
    public function hydrate(array $data, $object): bool
    {
        $column = $this->getDbColumn();
        $value = $data[$column] ?? false;

        if (mb_strtolower($value) === 'false') {
            $value = false;
        } else {
            $value = (bool) $data[$column];
        }

        RepoHelper::setProperty($object, $this->getModelProperty(), $value);
        return $value;
    }

    /**
     * @param $object
     * @return array
     */
    public function extract($object): array
    {
        /** @var bool $value */
        $value = RepoHelper::getProperty($object, $this->getModelProperty());

        return [
            $this->getDbColumn() => (int) $value
        ];
    }

    public function getFixtures(): array
    {
        return [
            0,
            1,
        ];
    }
}