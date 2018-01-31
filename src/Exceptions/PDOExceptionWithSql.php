<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 21.12.2017 11:24
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\Exceptions;


use Throwable;

class PDOExceptionWithSql extends \PDOException
{

    public function __construct(string $sql, array $params, Throwable $previous)
    {
        $message = 'SQL exception: ' . $sql . PHP_EOL . 'Params: ' . print_r($params, true);
        parent::__construct($message, $this->getCode(), $previous);
    }

}