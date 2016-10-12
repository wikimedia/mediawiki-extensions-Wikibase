<?php

namespace Wikibase\DataModel\Tests\Entity;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;

/**
 * @covers Wikibase\DataModel\Entity\ItemIdParser
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class ItemIdParserTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testCanParseEntityId( $idString, ItemId $expected ) {
		$parser = new ItemIdParser();
		$actual = $parser->parse( $idString );

		$this->assertEquals( $actual, $expected );
	}

	public function entityIdProvider() {
		return [
			[ 'q42', new ItemId( 'Q42' ) ],
			[ 'Q1337', new ItemId( 'Q1337' ) ],
		];
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotParseInvalidId( $invalidIdSerialization ) {
		$parser = new ItemIdParser();

		$this->setExpectedException( 'Wikibase\DataModel\Entity\EntityIdParsingException' );
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
			[ 'p1' ],
			[ 'P100000' ],
		];
	}

}
