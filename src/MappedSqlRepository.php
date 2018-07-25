<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 07.02.2018 17:42
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql;


use Adbar\Dot;
use Aura\Sql\ExtendedPdo;
use DjinORM\Djin\Id\IdGeneratorInterface;
use DjinORM\Djin\Mappers\ArrayMapperInterface;
use DjinORM\Djin\Mappers\Handler\MappersHandler;
use DjinORM\Djin\Mappers\Handler\MappersHandlerInterface;
use DjinORM\Djin\Mappers\NestedMapperInterface;
use DjinORM\Djin\Model\ModelInterface;
use DjinORM\Djin\Repository\MappedRepositoryInterface;
use DjinORM\Repositories\Sql\Components\MappedQueryFactory;
use DjinORM\Repositories\Sql\Components\MappedQuoter;

abstract class MappedSqlRepository extends SqlRepository implements MappedRepositoryInterface
{

    protected $mapperHandler;

    public function __construct(ExtendedPdo $pdo, MappedQueryFactory $factory, IdGeneratorInterface $idGenerator, array $slaves = [])
    {
        $quoter = new MappedQuoter(function (string $value) {
            return $this->getAlias($value);
        });
        $factory->setQuoter($quoter);
        parent::__construct($pdo, $factory, $idGenerator, $slaves);
    }

    abstract protected function map(): array;

    public function getMappersHandler(): MappersHandlerInterface
    {
        if (null === $this->mapperHandler) {
            $this->mapperHandler = new MappersHandler(static::getModelClass(), $this->map());
        }
        return $this->mapperHandler;
    }

    public function getNotationString(): string
    {
        return '___';
    }

    public function getAlias(string $modelProperty): string
    {
        $alias = $this->getMappersHandler()->getModelPropertyToDbAlias($modelProperty);
        if ($alias === null) {
            $alias = $modelProperty;
        }
        return str_replace('.', $this->getNotationString(), $alias);
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
        $dotData = $this->convertNotation($data, $this->getNotationString(), '.');
        $data = new Dot($this->fromDotToArray($dotData));
        $nestedNulls = array_diff_key($dotData, $data->flatten('.'));
        krsort($nestedNulls);
        foreach ($nestedNulls as $key => $value) {
            if ((int) $value < 1) {
                $data->set($key, null);
            }
        }

        foreach ($data->flatten('.') as $key => $value) {
            $mapper = $this->getMappersHandler()->getMapperByDbAlias($key);
            if ($mapper instanceof ArrayMapperInterface) {
                if (is_string($value)) {
                    $value = json_decode($value, true);
                    $data->set($key, $value);
                }
            }
        }

        $data = array_merge($this->getScheme()->all(), $data->all());
        return $this->getMappersHandler()->hydrate($data);
    }

    /**
     * @param ModelInterface $object
     * @return array
     */
    protected function extract(ModelInterface $object): array
    {
        $data = $this->getMappersHandler()->extract($object);
        $this->extractRecursive('', $data);
        return $this->convertNotation($data, '.', $this->getNotationString());
    }

    protected function extractRecursive(string $prefix, array &$data)
    {
        if (!empty($prefix)) {
            $prefix.= '.';
        }

        foreach ($data as $key => $value) {
            $path = $prefix . $key;
            $mapper = $this->getMappersHandler()->getMapperByDbAlias($path);

            if ($mapper instanceof ArrayMapperInterface) {
                if (is_array($value)) {
                    $data[$key] = json_encode($value);
                }
                continue;
            }

            if ($mapper instanceof NestedMapperInterface) {

                if (is_array($value)) {
                    $this->extractRecursive($path, $value);
                }

                unset($data[$key]);

                $nullCheckerArray = [$key => (int) (bool) $value];
                if (null === $value) {
                    $value = array_fill_keys(
                        array_keys($mapper->getNestedMappersHandler()->getDbAliasesToModelProperties()),
                        null
                    );
                }

                $nestedData = array_merge($nullCheckerArray, (new Dot([$key => $value]))->flatten('.'));
                $data = array_merge($data, $nestedData);
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

    private function convertNotation($array, string $from, string $to): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[str_replace($from, $to, $key)] = $value;
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