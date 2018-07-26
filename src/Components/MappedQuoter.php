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

    protected function replaceNamesIn($text)
    {
        $regexp = '~(\b)([a-z_][a-z0-9_]*)(\.([a-z_][a-z0-9_]*)(\b))+~ui';
        $text = preg_replace_callback($regexp, function ($value) {
            return ($this->quoteCallback)($value[0]);
        }, $text);
        return parent::replaceNamesIn($text);
    }

}