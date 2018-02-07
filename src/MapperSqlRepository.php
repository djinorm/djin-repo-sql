<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 07.02.2018 17:42
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql;


use DjinORM\Djin\Mappers\Mapper;
use DjinORM\Djin\Model\ModelInterface;

abstract class MapperSqlRepository extends SqlRepository
{

    /**
     * @return Mapper
     */
    abstract protected function getMapper(): Mapper;

    /**
     * Превращает массив в объект нужного класса
     * @param array $data
     * @return ModelInterface
     * @throws \DjinORM\Djin\Exceptions\MismatchModelException
     * @throws \ReflectionException
     */
    protected function hydrate(array $data): ModelInterface
    {
        return $this->getMapper()->hydrate($data);
    }

    /**
     * @param ModelInterface $object
     * @return array
     */
    protected function extract(ModelInterface $object): array
    {
        return $this->getMapper()->extract($object);
    }

}