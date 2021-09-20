<?php

namespace Wikibase\DataModel\Tests\Entity;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;

/**
 * @covers \Wikibase\DataModel\Entity\BasicEntityIdParser
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
 */
class BasicEntityIdParserTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testCanParseEntityId( $idString, EntityId $expected ) {
		$parser = new BasicEntityIdParser();
		$actual = $parser->parse( $idString );

		$this->assertEquals( $expected, $actual );
	}

	public function entityIdProvider() {
		return [
			[ 'q42', new ItemId( 'q42' ) ],
			[ 'Q1337', new ItemId( 'Q1337' ) ],
			[ 'p1', new NumericPropertyId( 'p1' ) ],
			[ 'P100000', new NumericPropertyId( 'P100000' ) ],
		];
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotParseInvalidId( $invalidIdSerialization ) {
		$parser = new BasicEntityIdParser();

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
		];
	}

}
