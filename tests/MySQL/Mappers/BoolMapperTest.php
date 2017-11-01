<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 01.11.2017 17:12
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\MySQL\Mappers;

use DjinORM\Djin\TestHelpers\MockForMapperTest;
use PHPUnit\Framework\TestCase;

class BoolMapperTest extends TestCase
{

    /** @var MockForMapperTest */
    protected $testClassValue;

    /** @var BoolMapper */
    protected $mapper;

    public function setUp()
    {
        $this->mapper = new BoolMapper('value');
        $this->testClassValue = new MockForMapperTest(true);
    }

    public function testHydrate()
    {
        $mapper = $this->mapper;
        $mapper->hydrate([], $this->testClassValue);
        $this->assertFalse($this->testClassValue->getScalarValue());

        foreach ($mapper->getFixtures() as $fixture) {
            $mapper->hydrate(['value' => $fixture], $this->testClassValue);
            $this->assertEquals($fixture, $this->testClassValue->getScalarValue());
        }
    }

    public function testExtractAllowNull()
    {
        $mapper = $this->mapper;

        $this->assertEquals(
            ['value' => $this->testClassValue->getScalarValue()],
            $mapper->extract($this->testClassValue)
        );
    }

    public function testGetFixtures()
    {
        $this->assertEquals([0,1], $this->mapper->getFixtures());
    }
}
