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
use DjinORM\Djin\Mappers\Mapper;
use DjinORM\Djin\Mappers\ScalarMapper;

class ArrayMapper extends ScalarMapper
{

    /**
     * @var Mapper
     */
    private $nestedMapper;
    /**
     * @var bool
     */
    private $allowNullNested;

    public function __construct(
        string $modelProperty,
        string $dbColumn = null,
        bool $allowNull = false,
        Mapper $nestedMapper = null,
        bool $allowNullNested = true
    )
    {
        parent::__construct($modelProperty, $dbColumn, $allowNull);
        $this->nestedMapper = $nestedMapper;
        $this->allowNullNested = $allowNullNested;
    }

    /**
     * @param array $data
     * @param object $object
     * @return array|null
     * @throws HydratorException
     * @throws \ReflectionException
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

        if ($this->nestedMapper) {
            $array = array_map(function ($data) use ($object){
                if (null === $data) {
                    if ($this->isAllowNullNested()) {
                        return null;
                    }
                    return new HydratorException("Null instead of nested object is not allowed in " . $this->getDescription($object));
                }
                return $this->nestedMapper->hydrate($data);
            }, $array);
        }

        if ($array === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new HydratorException('Json parse error: ' . json_last_error_msg(), 1);
        }

        RepoHelper::setProperty($object, $this->getModelProperty(), $array);
        return $array;
    }

    /**
     * @param $object
     * @return array
     * @throws ExtractorException
     * @throws \ReflectionException
     */
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

        if ($this->nestedMapper) {
            $array = array_map(function ($nestedObject) use ($object) {
                if (null === $nestedObject) {
                    if ($this->isAllowNullNested()) {
                        return null;
                    }
                    new ExtractorException("Impossible to save null instead of nested object from " . $this->getDescription($object));
                }
                return $this->nestedMapper->extract($nestedObject);
            }, $array);
        }

        $json = \json_encode($array);
        if ($json === false && json_last_error() !== JSON_ERROR_NONE) {
            throw new ExtractorException('Json encode error: ' . json_last_error_msg(), 1);
        }

        return [
            $this->getDbColumn() => $json
        ];
    }

    /**
     * @return bool
     */
    public function isAllowNullNested(): bool
    {
        return $this->allowNullNested;
    }
}