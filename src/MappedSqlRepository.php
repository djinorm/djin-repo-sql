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
use DjinORM\Djin\Mappers\NestedMapper;
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

    public function getMappersHandler(): MappersHandler
    {
        if (null === $this->mapperHandler) {
            $this->mapperHandler = new MappersHandler(static::getModelClass(), $this->map());
        }
        return $this->mapperHandler;
    }

    /**
     * Строка, которая используется как разделитель вложенных сущностей при преобразовании их в плоскую
     * структуру. Это могла бы быть точечная нотация, но с ней есть проблемы в ряде СУБД
     * @return string
     */
    public function getNotationString(): string
    {
        return '___';
    }

    /**
     * Возвращает имя поля id в базе
     * @return string
     */
    protected function getIdName(): string
    {
        /** @var ModelInterface $class */
        $class = $this->getModelClass();
        return $class::getModelIdPropertyName();
    }

    /**
     * Извлекает данные из модели и возвращает их в формате, пригодном для сохранения в таблицу.
     * @param ModelInterface $object
     * @return array
     */
    protected function extract(ModelInterface $object): array
    {
        $data = $this->getMappersHandler()->extract($object);
        return $this->convertExtractedData($data);
    }

    /**
     * Преобразовывает извлеченные мапперами данные в формат, требуемый для сохранения в базу
     * @param array $data
     * @return array
     */
    protected function convertExtractedData(array $data): array
    {
        $this->extractRecursive('', $data);
        return $this->convertNotation($data, '.', $this->getNotationString());
    }

    /**
     * Преобразует структуру данных, извлеченную из модели при помощи мапперов в плоский массив,
     * пригодный для сохранения в таблицу. Например, структура вида
     *
     * [
     *      'data' => [
     *          'nested' => [
     *              'id' => 10,
     *              'name' => 'Timur',
     *          ]
     *      ]
     * ]
     *
     * будет преобразована в массив вида
     *
     * [
     *      'data' => true,
     *      'data___nested' => true,
     *      'data___nested___id' => 10,
     *      'data___nested___name' => 'Timur',
     * ]
     *
     * В примере выше 'data' => true и 'data___nested' => true нужны для определения существования
     * вложенных объектов. Они нужны только если @see NestedMapper разрешает null. Если не разрешает,
     * то их использование не обязательно
     *
     * @param string $prefix - путь в точечной нотации, который нужно сделать плоским
     * @param array $data - массив данных, переданный по ссылке
     */
    protected function extractRecursive(string $prefix, array &$data)
    {
        if (!empty($prefix)) {
            $prefix.= '.';
        }

        foreach ($data as $key => $value) {
            $path = $prefix . $key;
            $mapper = $this->getMappersHandler()->getMapperByProperty($path);

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

                if ($mapper->isNullAllowed()) {
                    $nullCheckerArray = [$key => (int) (bool) $value];
                } else {
                    $nullCheckerArray = [];
                }

                $scheme = new Dot([$key => $mapper->getNestedMappersHandler()->getScheme()]);
                $nestedData = array_merge($nullCheckerArray, ($scheme->flatten('.')));
                $data = array_merge($nestedData, $data);
            }
        }
    }

    /**
     * Превращает массив в объект нужного класса
     * @param array $data
     * @return ModelInterface
     * @throws \ReflectionException
     */
    protected function hydrate(array $data): ModelInterface
    {
        $data = $this->convertHydrationData($data);
        return $this->getMappersHandler()->hydrate($data);
    }

    /**
     * Преобразует извлеченные плоские данные из БД во вложенную структуру, пригодную для гидрации
     * модели. Например, структура вида
     *
     * [
     *      'data' => true,
     *      'data___nested' => true,
     *      'data___nested___id' => 10
     *      'data___nested___name' => 'Timur'
     * ]
     *
     * будет преобразована в массив вида
     * [
     *      'data' => [
     *          'nested' => [
     *              'id' => 10,
     *              'name' => 'Timur',
     *          ]
     *      ]
     * ]
     *
     * В примере выше 'data' => true и 'data___nested' => true нужны для определения существования
     * вложенных объектов. Они нужны только если @see NestedMapper разрешает null. Если не разрешает,
     * то их использование не обязательно
     *
     * @param array $data
     * @return array
     */
    protected function convertHydrationData(array $data): array
    {
        //Преобразование данных в точечную нотацию
        $dotData = $this->convertNotation($data, $this->getNotationString(), '.');

        //Объект Dot с возможностью обращения к массиву точечной нотацией
        $data = new Dot($this->fromDotToArray($dotData));

        //Здесь мы определяем, какие из вложенных объектов у нас не заданы (должны быть null)
        $nestedNulls = array_diff_key($dotData, $data->flatten('.'));
        krsort($nestedNulls);
        foreach ($nestedNulls as $key => $value) {
            if ((int) $value < 1) {
                $mapper = $this->getMappersHandler()->getMapperByProperty($key);
                if ($mapper->isNullAllowed()) {
                    $data->set($key, null);
                }
            }
        }

        //Массивы в плоском виде в таблице реляционной БД можно хранить только в виде json, поэтому
        //мы смотрим на мапперы, реализующие ArrayMapperInterface и делаем для этих данных json_decode
        foreach ($data->flatten('.') as $key => $value) {
            $mapper = $this->getMappersHandler()->getMapperByProperty($key);
            if ($mapper instanceof ArrayMapperInterface) {
                if (is_string($value)) {
                    $value = json_decode($value, true);
                    $data->set($key, $value);
                }
            }
        }

        return $data->all();
    }

    /**
     * Возвращает схему, которые извлекают мапперы из модели, но вместо реальных значений везде null.
     * Используется в @see convertHydrationData() c целью перезаписи в базе существующих значений
     * вложенных объектов, когда вложенные объекты были убраны (установлены в null)
     * @return Dot
     */
    protected function getScheme(): Dot
    {
        $scheme = $this->getMappersHandler()->getScheme();
        return new Dot($this->fromDotToArray($scheme));
    }

    /**
     * Преобразование нотаций. Например, из точечной нотации в ___ и наоборот
     * @param $array
     * @param string $from
     * @param string $to
     * @return array
     */
    protected function convertNotation($array, string $from, string $to): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[str_replace($from, $to, $key)] = $value;
        }
        return $result;
    }

    /**
     * Преобразует точечную нотацию в обычный массив
     * @param $array
     * @return array
     */
    protected function fromDotToArray($array): array
    {
        $dot = new Dot();
        foreach ($array as $key => $value) {
            $dot->set($key, $value);
        }
        return $dot->all();
    }

}