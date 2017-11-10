<?php
/**
 * Created for djin-repo-sql.
 * Datetime: 10.11.2017 15:30
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DjinORM\Repositories\Sql\MySQL\Mappers;


use DjinORM\Djin\Exceptions\ExtractorException;
use DjinORM\Djin\Exceptions\HydratorException;
use DjinORM\Djin\TestHelpers\MapperTestCase;

class ArrayMapperTest extends MapperTestCase
{

    public function testHydrate()
    {
        $this->assertHydrated(null, null, $this->getMapperAllowNull());
        $this->assertHydrated(null, '', $this->getMapperAllowNull());

        $this->assertHydrated([], '[]', $this->getMapperDisallowNull());
        $this->assertHydrated([1, 2, 3], '[1, 2, 3]', $this->getMapperDisallowNull());
        $this->assertHydrated([1, 2, 3, 4 => [5, 6]], '{"0": 1, "1": 2, "2": 3, "4": [5, 6]}', $this->getMapperDisallowNull());

        $this->expectException(HydratorException::class);
        $this->assertHydrated(null, null, $this->getMapperDisallowNull());
    }

    public function testHydrateParseError()
    {
        $this->expectException(HydratorException::class);
        $this->expectExceptionCode(1);
        $this->assertHydrated([], 'qwerty', $this->getMapperDisallowNull());
    }

    public function testExtract()
    {
        $this->assertExtracted(null, null, $this->getMapperAllowNull());
        $this->assertExtracted(null, '', $this->getMapperAllowNull());

        $this->assertExtracted('[]', [], $this->getMapperAllowNull());
        $this->assertExtracted('[1,2,3]', [1, 2, 3], $this->getMapperAllowNull());
        $this->assertExtracted('{"0":1,"1":2,"2":3,"4":[5,6]}', [1, 2, 3, 4 => [5, 6]], $this->getMapperAllowNull());

        $this->expectException(ExtractorException::class);
        $this->assertExtracted(null, null, $this->getMapperDisallowNull());
    }

    public function testExtractParseError()
    {
        $this->expectException(ExtractorException::class);
        $this->expectExceptionCode(1);

        /** @noinspection PhpUndefinedVariableInspection */
        $array = ['key' => &$array];

        $this->assertExtracted([], $array, $this->getMapperDisallowNull());
    }

    protected function getMapperAllowNull(): ArrayMapper
    {
        return new ArrayMapper('value', 'value', true);
    }

    protected function getMapperDisallowNull(): ArrayMapper
    {
        return new ArrayMapper('value', 'value', false);
    }
}
