<?php

namespace Wikibase\DataModel\Services\Tests\EntityId;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParser;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @covers \Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParser
 *
 * @license GPL-2.0-or-later
 */
class PrefixMappingEntityIdParserTest extends TestCase {

	private function getRegularParser() {
		return new BasicEntityIdParser();
	}

	/**
	 * @dataProvider provideValidIdSerialization
	 */
	public function testGivenOnlyDefaultPrefixMapping_prefixIsAddedToIdSerialization(
		array $prefixMapping, $input, EntityId $expectedId
	) {
		$parser = new PrefixMappingEntityIdParser(
			$prefixMapping,
			$this->getRegularParser()
		);
		$this->assertEquals( $expectedId, $parser->parse( $input ) );
	}

	public function provideValidIdSerialization() {
		return [
			[ [ '' => 'wikidata' ], 'Q1337', new ItemId( 'wikidata:Q1337' ) ],
			[ [ '' => '' ], 'Q1337', new ItemId( 'Q1337' ) ],
			[ [ '' => 'foo' ], 'P123', new NumericPropertyId( 'foo:P123' ) ],
			[ [ '' => 'foo' ], 'bar:Q1337', new ItemId( 'foo:bar:Q1337' ) ],
			[ [ '' => 'foo' ], 'foo:Q1337', new ItemId( 'foo:foo:Q1337' ) ],
		];
	}

	/**
	 * @dataProvider provideMappedSerializationId
	 */
	public function testGivenPrefixIsMapped_mappedOneIsUsed(
		array $prefixMapping,
		$foreignId,
		EntityId $expectedEntityId
	) {
		$regularParser = $this->getRegularParser();
		$parser = new PrefixMappingEntityIdParser( $prefixMapping, $regularParser );
		$this->assertEquals( $expectedEntityId, $parser->parse( $foreignId ) );
	}

	public function provideMappedSerializationId() {
		return [
			[ [ '' => 'foo', 'bar' => 'wd' ], 'bar:Q1337', new ItemId( 'wd:Q1337' ) ],
			[ [ '' => 'foo', 'bar' => 'wd' ], 'bar:x:Q1337', new ItemId( 'wd:x:Q1337' ) ],
		];
	}

	public function testGivenPrefixIsNotMapped_defaultPrefixIsAddedToIdSerialization() {
		$expectedId = new ItemId( 'foo:x:bar:Q1337' );
		$regularParser = $this->getRegularParser();
		$parser = new PrefixMappingEntityIdParser( [ '' => 'foo', 'bar' => 'wd' ], $regularParser );
		$this->assertEquals( $expectedId, $parser->parse( 'x:bar:Q1337' ) );
	}

	public function testEntityIdParsingExceptionsAreNotCaught() {
		$regularParser = $this->createMock( EntityIdParser::class );
		$regularParser->expects( $this->any() )
			->method( 'parse' )
			->will( $this->throwException( new EntityIdParsingException() ) );
		$parser = new PrefixMappingEntityIdParser( [ '' => 'wikidata' ], $regularParser );
		$this->expectException( EntityIdParsingException::class );
		$parser->parse( 'QQQ' );
	}

	/**
	 * @dataProvider provideInvalidPrefixMapping
	 */
	public function testGivenInvalidPrefixMapping_exceptionIsThrown( array $prefixMapping ) {
		$this->expectException( ParameterAssertionException::class );
		new PrefixMappingEntityIdParser( $prefixMapping, new ItemIdParser() );
	}

	public function provideInvalidPrefixMapping() {
		return [
			'no empty-string key in prefix mapping' => [ [] ],
			'non-string values in prefix mapping' => [ [ '' => 123 ] ],
			'non-string keys in prefix mapping' => [ [ '' => 'foo', 0 => 'wd' ] ],
			'prefix mapping values containing colons' => [ [ '' => 'wikidata:' ] ],
		];
	}

}
