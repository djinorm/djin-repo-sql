<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 25.07.2018 17:39
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\Components;


use Aura\SqlQuery\QueryFactory;

class MappedQueryFactory extends QueryFactory
{

    public function setQuoter(MappedQuoter $quoter)
    {
        $this->quoter = $quoter;
        $this->quoter->setQuoteNamePrefix($this->quote_name_prefix);
        $this->quoter->setQuoteNameSuffix($this->quote_name_suffix);
    }

}