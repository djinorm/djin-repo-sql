<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 26.07.2018 12:52
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\Components;

use PHPUnit\Framework\TestCase;

class MappedQuoterTest extends TestCase
{

    /** @var MappedQuoter */
    private $quoter;

    protected function setUp()/* The :void return type declaration that should be here would cause a BC issue */
    {
        parent::setUp();
        $this->quoter = new MappedQuoter(function ($value) {
            return strrev($value);
        });
    }

    public function testSetQuoteNamePrefix()
    {
        $this->assertEmpty($this->quoter->getQuoteNamePrefix());
        $this->assertEmpty($this->quoter->getQuoteNameSuffix());

        $this->quoter->setQuoteNamePrefix('<');
        $this->assertEquals('<', $this->quoter->getQuoteNamePrefix());

        $this->assertEmpty($this->quoter->getQuoteNameSuffix());
    }

    public function testSetQuoteNameSuffix()
    {
        $this->assertEmpty($this->quoter->getQuoteNamePrefix());
        $this->assertEmpty($this->quoter->getQuoteNameSuffix());

        $this->quoter->setQuoteNameSuffix('>');
        $this->assertEquals('>', $this->quoter->getQuoteNameSuffix());

        $this->assertEmpty($this->quoter->getQuoteNamePrefix());
    }

    public function testQuoteName()
    {
        $this->assertEquals('ytrewq', $this->quoter->quoteName('qwerty'));
        $this->assertEquals('ytrewq.ewq', $this->quoter->quoteName('qwerty.qwe'));
        $this->assertEquals('ytrewq AS ewq', $this->quoter->quoteName('qwerty as qwe'));
        $this->assertEquals('ytrewq.ewq AS ewq', $this->quoter->quoteName('qwerty.qwe as qwe'));
    }
}
