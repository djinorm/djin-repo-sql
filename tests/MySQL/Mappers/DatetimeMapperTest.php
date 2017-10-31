<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 31.10.2017 12:16
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\MySQL\Mappers;

use DjinORM\Djin\Mappers\ScalarMapper;
use DjinORM\Djin\TestHelpers\MockForMapperTest;
use DjinORM\Djin\TestHelpers\ScalarMapperTestCase;

class DatetimeMapperTest extends ScalarMapperTestCase
{

    public function setUp()
    {
        $this->testClassValue = new class(new \DateTime('2017-10-31 12:47')) extends MockForMapperTest{
            /** @var \DateTimeInterface */
            public $value;

            public function getScalarValue()
            {
                return $this->value ? $this->value->format('Y-m-d H:i:s') : null;
            }
        };

        $this->testClassNull = new class() extends MockForMapperTest {
            /** @var \DateTimeInterface */
            public $value;

            public function getScalarValue()
            {
                return $this->value ? $this->value->format('Y-m-d H:i:s') : null;
            }
        };
    }

    public function testGetFixtures()
    {
        $this->assertGetFixtures([
            '2001-01-01 01:01:01',
            '2002-02-02 02:02:02',
            '2003-03-03 03:03:03',
            '2004-04-04 04:04:04',
            '2005-05-05 05:05:05',
        ]);
    }

    protected function getTestClassValue()
    {
        return true;
    }

    protected function getMapperAllowNull(): ScalarMapper
    {
        return new DatetimeMapper('value', 'value', true);
    }

    protected function getMapperDisallowNull(): ScalarMapper
    {
        return new DatetimeMapper('value', 'value', false);
    }
}
