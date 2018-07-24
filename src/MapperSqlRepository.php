<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 07.02.2018 17:42
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql;


use Adbar\Dot;
use DjinORM\Djin\Mappers\ArrayMapper;
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

    public function getAlias(string $modelProperty): string
    {
        $alias = $this->getMappersHandler()->getModelPropertyToDbAlias($modelProperty);
        return str_replace('.', '___', $alias);
    }

    protected function getIdName(): string
    {
        /** @var ModelInterface $class */
        $class = $this->getModelClass();
        return $this->getAlias($class::getModelIdPropertyName());
    }

    /**
     * Превращает массив в объект нужного класса
     * @param array $data
     * @return ModelInterface
     */
    protected function hydrate(array $data): ModelInterface
    {
        $this->hydrateConvertRecursive('', $data);
        $data = array_merge(
            $this->getScheme()->all(),
            $data
        );
        return $this->getMappersHandler()->hydrate($data);
    }

    protected function hydrateConvertRecursive(string $prefix, array &$data)
    {
        $data = $this->fromDashToDot($data);
        $data = $this->fromDotToArray($data);

        if (!empty($prefix)) {
            $prefix = $prefix . '.';
        }

        foreach ($data as $key => $value) {
            $path = $prefix . $key;

            $mapper = $this->getMappersHandler()->getMapperByDbAlias($path);

            $isArrayMapper = $mapper instanceof ArrayMapperInterface;
            $isNestedMapper = $mapper instanceof NestedMapperInterface;

            if ($isArrayMapper) {
                if (!is_array($data[$key])) {
                    $data[$key] = json_decode($value, true);
                }
            }

            if (is_array($data[$key])) {
                $this->hydrateConvertRecursive($path, $data[$key]);

                if (($isArrayMapper || $isNestedMapper) && $mapper->isNullAllowed()) {
                    $data[$key] = $this->collapseNullArray($data[$key]);
                }
            }
        }

        return $data;
    }

    protected function collapseNullArray(?array $array): ?array
    {
        if (null === $array || empty($array)) {
            return null;
        }

        $array = array_filter($array, function ($value) {
            return $value !== null;
        });

        return count($array) ? $array : null;
    }

    /**
     * @param ModelInterface $object
     * @return array
     */
    protected function extract(ModelInterface $object): array
    {
        $data = $this->getMappersHandler()->extract($object);
        $this->extractConvertRecursive('', $data);
        return $data;
    }

    protected function extractConvertRecursive(string $prefix, array &$data)
    {
        $prefix = empty($prefix) ? '' : ($prefix . '.');
        foreach ($data as $dbAlias => $value) {
            $path = $prefix . $dbAlias;
            $mapper = $this->getMappersHandler()->getMapperByDbAlias($path);
            if (null === $mapper) {
                continue;
            }

            if ($mapper instanceof ArrayMapper) {
                if (null !== $value) {
                    $this->extractConvertRecursive($path, $value);
                }
                $data[$dbAlias] = json_encode($value);
            }

            if ($mapper instanceof NestedMapperInterface) {
                if (null !== $value) {
                    $this->extractConvertRecursive($path, $value);
                } else {
                    $value = [];
                }
                $scheme = $this->getScheme()->get($path);
                $extracted = array_merge($scheme, $value);
                unset($data[$dbAlias]);
                $flatten = (new Dot([$dbAlias => $extracted]))->flatten('___');
                $data = array_merge($data, $flatten);
            }
        }
    }

    private function getScheme(): Dot
    {
        $db2model = $this->getMappersHandler()->getDbAliasesToModelProperties();
        $scheme = array_map(function () {
            return null;
        }, $db2model);
        return new Dot($this->fromDotToArray($scheme));
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