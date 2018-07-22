<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 07.02.2018 17:42
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql;


use Adbar\Dot;
use DjinORM\Djin\Mappers\ArrayMapperInterface;
use DjinORM\Djin\Mappers\Handler\MappersHandler;
use DjinORM\Djin\Mappers\Handler\MappersHandlerInterface;
use DjinORM\Djin\Mappers\NestedMapperInterface;
use DjinORM\Djin\Model\ModelInterface;
use DjinORM\Djin\Repository\MapperRepositoryInterface;

abstract class MapperSqlRepository extends SqlRepository implements MapperRepositoryInterface
{

    private $mapperHandler;

    abstract protected function map(): array;

    public function getMappersHandler(): MappersHandlerInterface
    {
        if (null === $this->mapperHandler) {
            $this->mapperHandler = new MappersHandler(static::getModelClass(), $this->map());
        }
        return $this->mapperHandler;
    }

    /**
     * Превращает массив в объект нужного класса
     * @param array $data
     * @return ModelInterface
     */
    protected function hydrate(array $data): ModelInterface
    {
        $data = $this->fromDashToDot($data);
        $data = $this->fromDotToArray($data);

        foreach ($this->getMappersHandler()->getDbAliasesToModelProperties() as $dbAlias => $modelProperty) {
            if ($mapper = $this->getMappersHandler()->getMappers()[$modelProperty] ?? null) {

                if ($mapper instanceof ArrayMapperInterface) {
                    $data[$dbAlias] = json_decode($data[$dbAlias], true);
                }

                if ($mapper instanceof NestedMapperInterface) {
                    $exists = false;
                    foreach ($data[$dbAlias] as $value) {
                        if ($value !== null) {
                            $exists = true;
                        }
                    }
                    if ($exists === false) {
                        $data[$dbAlias] = null;
                    }
                }
            }
        }
        return $this->getMappersHandler()->hydrate($data);
    }

    /**
     * @param ModelInterface $object
     * @return array
     */
    protected function extract(ModelInterface $object): array
    {
        $data = $this->getMappersHandler()->extract($object);
        $db2model = $this->getMappersHandler()->getDbAliasesToModelProperties();
        $db2modelNested = $this->fromDotToArray($db2model);

        foreach ($db2model as $dbAlias => $modelProperty) {

            if ($mapper = $this->getMappersHandler()->getMappers()[$modelProperty] ?? null) {

                if ($mapper instanceof ArrayMapperInterface) {
                    $data[$dbAlias] = json_encode($data[$dbAlias]);
                }

                if ($mapper instanceof NestedMapperInterface) {
                    if ($data[$dbAlias] === null) {
                        $values = array_combine(
                            array_keys($db2modelNested[$dbAlias]),
                            array_fill(0, count($db2modelNested[$dbAlias]), null)
                        );
                        $nested = new Dot([$dbAlias => $values]);
                    } else {
                        $nested = new Dot([$dbAlias => $data[$dbAlias]]);
                    }
                    unset($data[$dbAlias]);
                    $data = array_merge($data, $nested->flatten('___'));
                }

            }
        }

        return $data;
    }

    private function fromDashToDot($array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[str_replace('___', '.', $key)] = $value;
        }
        return $result;
    }

    private function fromDotToArray($array): array
    {
        $dot = new Dot();
        foreach ($array as $key => $value) {
            $dot->set($key, $value);
        }
        return $dot->all();
    }

}