<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 18.07.2018 14:09
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\Fakes;


class Money
{

    public $Amount;
    public $Currency;

    public function __construct(int $amount, string $currency)
    {
        $this->Amount = $amount;
        $this->Currency = $currency;
    }

}