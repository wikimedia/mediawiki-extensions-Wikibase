<?php

namespace Wikibase\DataModel\Tests\Entity;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;

/**
 * @covers \Wikibase\DataModel\Entity\DispatchingEntityIdParser
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
 */
class DispatchingEntityIdParserTest extends \PHPUnit\Framework\TestCase {

	private function getBasicParser() {
		return new DispatchingEntityIdParser( BasicEntityIdParser::getBuilders() );
	}

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testCanParseEntityId( $idString, EntityId $expected ) {
		$parser = $this->getBasicParser();
		$actual = $parser->parse( $idString );

		$this->assertEquals( $expected, $actual );
	}

	public function entityIdProvider() {
		return [
			[ 'q42', new ItemId( 'q42' ) ],
			[ 'Q1337', new ItemId( 'Q1337' ) ],
			[ 'p1', new NumericPropertyId( 'p1' ) ],
			[ 'P100000', new NumericPropertyId( 'P100000' ) ],
			[ 'foo:Q1337', new ItemId( 'foo:Q1337' ) ],
			[ 'foo:P123', new NumericPropertyId( 'foo:P123' ) ],
			[ 'foo:bar:Q1337', new ItemId( 'foo:bar:Q1337' ) ],
			[ ':Q1337', new ItemId( ':Q1337' ) ],
		];
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotParseInvalidId( $invalidIdSerialization ) {
		$parser = $this->getBasicParser();

		$this->expectException( EntityIdParsingException::class );
		$parser->parse( $invalidIdSerialization );
	}

	public function invalidIdSerializationProvider() {
		return [
			[ 'FOO' ],
			[ null ],
			[ 42 ],
			[ [] ],
			[ '' ],
			[ 'q0' ],
			[ '1p' ],
			[ 'foo:' ],
			[ 'foo:bar:' ],
			[ '::Q1337' ],
			[ ':' ],
			[ 'q:0' ],
		];
	}

	public function testCannotParseWithoutBuilders() {
		$parser = new DispatchingEntityIdParser( [] );

		$this->expectException( EntityIdParsingException::class );
		$parser->parse( 'Q1' );
	}

}
