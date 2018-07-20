<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 10.05.2018 16:58
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\Fakes;


use DjinORM\Djin\Id\Id;
use DjinORM\Djin\Model\ModelInterface;
use DjinORM\Djin\Model\ModelTrait;

class Model implements ModelInterface
{

    use ModelTrait;

    /** @var Id */
    public $id;

    /** @var string */
    public $name;

    /** @var array */
    public $Array;

    /** @var Money */
    public $Money;

    /** @var Money[] */
    public $Balances;

    public function __construct($id = null, string $name = '')
    {
        $this->id = new Id($id);
        $this->name = $name;
    }

}