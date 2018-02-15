<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 15.02.2018 11:25
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\MySQL\Mappers;


use DjinORM\Djin\Helpers\RepoHelper;
use DjinORM\Djin\Mappers\Mapper;
use DjinORM\Djin\Mappers\MapperInterface;

class SubclassMapper implements MapperInterface
{

    /**
     * @var string
     */
    private $modelProperty;
    /**
     * @var Mapper
     */
    protected $mapper;

    public function __construct(string $modelProperty, Mapper $mapper)
    {
        $this->modelProperty = $modelProperty;
        $this->mapper = $mapper;
    }

    /**
     * @param array $data
     * @param object $object
     * @return mixed
     * @throws \DjinORM\Djin\Exceptions\MismatchModelException
     * @throws \ReflectionException
     */
    public function hydrate(array $data, $object)
    {
        $subObject = $this->mapper->hydrate($data);
        RepoHelper::setProperty($object, $this->modelProperty, $subObject);
        return $subObject;
    }

    /**
     * @param $object
     * @return array
     * @throws \ReflectionException
     */
    public function extract($object): array
    {
        $subObject = RepoHelper::getProperty($object, $this->modelProperty);
        return $this->mapper->extract($subObject);
    }
}