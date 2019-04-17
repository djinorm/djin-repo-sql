<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 10.05.2018 16:58
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\Fakes;


use DjinORM\Djin\Id\Id;
use DjinORM\Djin\Model\ModelInterface;

class Model implements ModelInterface
{

    /** @var Id */
    public $id;

    /** @var string */
    public $name;

    public function __construct($id = null, string $name = '')
    {
        $this->id = new Id($id);
        $this->name = $name;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public static function getModelName(): string
    {
        return 'model';
    }
}