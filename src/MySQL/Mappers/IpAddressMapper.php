<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 02.11.2017 12:22
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\MySQL\Mappers;


use DjinORM\Djin\Exceptions\ExtractorException;
use DjinORM\Djin\Exceptions\HydratorException;
use DjinORM\Djin\Helpers\RepoHelper;
use DjinORM\Djin\Mappers\ScalarMapper;

class IpAddressMapper extends ScalarMapper
{

    /**
     * @var bool
     */
    private $isBinary;

    public function __construct($modelProperty, $dbColumn = null, $allowNull = false, $storeAsBinary = true)
    {
        parent::__construct($modelProperty, $dbColumn, $allowNull);
        $this->isBinary = $storeAsBinary;
    }

    /**
     * @param array $data
     * @param object $object
     * @return null|string
     * @throws HydratorException
     * @throws \ReflectionException
     */
    public function hydrate(array $data, $object): ?string
    {
        $column = $this->getDbColumn();

        if (!isset($data[$column]) || $data[$column] === '') {
            if ($this->isAllowNull()) {
                RepoHelper::setProperty($object, $this->getModelProperty(), null);
                return null;
            }
            throw $this->nullHydratorException('IP address', $object);
        }

        $ip = $this->isBinary ? inet_ntop($data[$column]) : $data[$column];

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new HydratorException(sprintf('Trying to hydrate invalid IP address "%s" in %s',
                $data[$column],
                $this->getDescription($object)
            ));
        }

        RepoHelper::setProperty($object, $this->getModelProperty(), $ip);
        return $ip;
    }

    /**
     * @param $object
     * @return array
     * @throws ExtractorException
     * @throws \ReflectionException
     */
    public function extract($object): array
    {
        $ip = RepoHelper::getProperty($object, $this->getModelProperty());

        if ($ip === null || $ip === '') {
            if ($this->isAllowNull() == false) {
                throw $this->nullExtractorException('IP address', $object);
            }
            return [
                $this->getDbColumn() => null
            ];
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new ExtractorException(sprintf('Trying to extract invalid IP address "%s" in %s',
                $ip,
                $this->getDescription($object)
            ));
        }

        return [
            $this->getDbColumn() => $this->isBinary ? inet_pton($ip) : $ip,
        ];
    }

}