<?php

namespace Wikibase\DataModel\Tests\Entity;

use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;

/**
 * @covers \Wikibase\DataModel\Entity\ItemIdParser
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class ItemIdParserTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testCanParseEntityId( $idString, ItemId $expected ) {
		$parser = new ItemIdParser();
		$actual = $parser->parse( $idString );

		$this->assertEquals( $expected, $actual );
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
			[ 'p1' ],
			[ 'P100000' ],
		];
	}

}
