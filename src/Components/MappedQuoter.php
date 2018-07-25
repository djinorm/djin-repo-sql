<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 25.07.2018 17:37
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\Components;


use Aura\SqlQuery\Quoter;

class MappedQuoter extends Quoter
{

    /**
     * @var callable
     */
    protected $quoteCallback;

    public function __construct(callable $quoteCallback)
    {
        $this->quoteCallback = $quoteCallback;
        parent::__construct('', '');
    }

    public function setQuoteNamePrefix(string $quote_name_prefix)
    {
        $this->quote_name_prefix = $quote_name_prefix;
    }

    public function setQuoteNameSuffix(string $quote_name_suffix)
    {
        $this->quote_name_suffix = $quote_name_suffix;
    }

    protected function replaceName($name)
    {
        $name = trim($name);
        if ($name == '*') {
            return $name;
        }

        return $this->quote_name_prefix
            . ($this->quoteCallback)($name)
            . $this->quote_name_suffix;
    }

}