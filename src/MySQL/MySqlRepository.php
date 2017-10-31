<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 30.09.2017 21:32
 */

namespace DjinORM\Repositories\Sql\MySQL;


use DjinORM\Repositories\Sql\SqlRepository;

abstract class MySqlRepository extends SqlRepository
{

    protected function filterColumnName($property): string
    {
        return '`' . parent::filterColumnName($property) . '`';
    }

}