<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 26.07.2018 13:11
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\Components;

use PHPUnit\Framework\TestCase;

class MappedQueryFactoryTest extends TestCase
{

    /** @var MappedQueryFactory */
    private $factory;

    protected function setUp()/* The :void return type declaration that should be here would cause a BC issue */
    {
        parent::setUp();
        $this->factory = new MappedQueryFactory('mysql');
    }

    public function testSetQuoter()
    {
        $quoter = new MappedQuoter(function ($value) {
            return strrev($value);
        });
        $this->assertEmpty($quoter->getQuoteNamePrefix());
        $this->assertEmpty($quoter->getQuoteNameSuffix());

        $this->factory->setQuoter($quoter);

        $this->assertSame($quoter, $this->factory->getQuoter());
        $this->assertEquals('`', $quoter->getQuoteNamePrefix());
        $this->assertEquals('`', $quoter->getQuoteNameSuffix());
    }

}
