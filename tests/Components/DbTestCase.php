<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 11.05.2018 13:28
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\Components;


use Aura\Sql\ExtendedPdo;
use DjinORM\Djin\TestHelpers\IdComparator;
use PHPUnit\DbUnit\DataSet\ArrayDataSet;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;

abstract class DbTestCase extends TestCase
{

    use TestCaseTrait {
        TestCaseTrait::setUp as dbSetUp;
    }

    /** @var ExtendedPdo */
    private static $pdo;

    /** @var MappedQueryFactory */
    private static $queryFactory;

    private $connection;

    protected function getPdo(): ExtendedPdo
    {
        if (DbTestCase::$pdo == null) {
            DbTestCase::$pdo = new ExtendedPdo($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
        }
        return DbTestCase::$pdo;
    }

    protected function getQueryFactory(): MappedQueryFactory
    {
        if (DbTestCase::$queryFactory == null) {
            $db = substr($GLOBALS['DB_DSN'], 0, strpos($GLOBALS['DB_DSN'], ':'));
            DbTestCase::$queryFactory = new MappedQueryFactory($db);
        }
        return DbTestCase::$queryFactory;
    }

    public function getConnection()
    {
        if ($this->connection === null) {
            $this->connection = $this->createDefaultDBConnection(
                $this->getPdo(),
                $GLOBALS['DB_DBNAME']
            );
        }
        return $this->connection;
    }

    protected function setUp()
    {
        parent::setUp();
        $this->dbSetUp();
    }

    abstract protected function getDataSet(): ArrayDataSet;

}