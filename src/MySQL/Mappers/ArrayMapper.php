<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 10.11.2017 15:17
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\MySQL\Mappers;


use DjinORM\Djin\Exceptions\ExtractorException;
use DjinORM\Djin\Exceptions\HydratorException;
use DjinORM\Djin\Helpers\RepoHelper;
use DjinORM\Djin\Mappers\ScalarMapper;

class ArrayMapper extends ScalarMapper
{

    /**
     * @param array $data
     * @param object $object
     * @return array|null
     * @throws \DjinORM\Djin\Exceptions\HydratorException
     */
    public function hydrate(array $data, $object): ?array
    {
        $column = $this->getDbColumn();

        if (!isset($data[$column]) || $data[$column] === '') {
            if ($this->isAllowNull()) {
                RepoHelper::setProperty($object, $this->getModelProperty(), null);
                return null;
            }
            throw $this->nullHydratorException('array', $object);
        }

        $array = \json_decode($data[$column], true);

        if ($array === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new HydratorException('Json parse error: ' . json_last_error_msg(), 1);
        }

        RepoHelper::setProperty($object, $this->getModelProperty(), $array);
        return $array;
    }

    public function extract($object): array
    {
        $array = RepoHelper::getProperty($object, $this->getModelProperty());

        if (!is_array($array) && !is_a($array, \JsonSerializable::class)) {
            if ($this->isAllowNull() == false) {
                throw $this->nullExtractorException('array', $object);
            }
            return [
                $this->getDbColumn() => null
            ];
        }

        $json = \json_encode($array);
        if ($json === false && json_last_error() !== JSON_ERROR_NONE) {
            throw new ExtractorException('Json encode error: ' . json_last_error_msg(), 1);
        }

        return [
            $this->getDbColumn() => $json
        ];
    }
}