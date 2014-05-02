<?php

namespace Wikibase\Test;

use Wikibase\Badge\BadgeException;
use Wikibase\Badge\BadgesParser;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Badge\BadgesParser
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class BadgesParserTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider parseProvider
	 */
	public function testParse( $expected, $badgeIds, $isValid ) {
		$badgesParser = new BadgesParser(
			new BasicEntityIdParser(),
			$this->getBadgeValidator( $isValid )
		);

		$result = $badgesParser->parse( $badgeIds );

		$this->assertEquals( $expected, $result );
	}

	public function parseProvider() {
		$expected = array(
			new ItemId( 'Q9000' ),
			new ItemId( 'Q9001' )
		);

		return array(
			array( $expected, array( 'Q9000', 'Q9001' ), true )
		);
	}

	/**
	 * @dataProvider parseThrowsExceptionProvider
	 */
	public function testParseThrowsException( $badgeIds, $isValid, $message ) {
		$badgesParser = new BadgesParser(
			new BasicEntityIdParser,
			$this->getBadgeValidator( $isValid )
		);

		$this->setExpectedException( 'Wikibase\Badge\BadgeException', $message );

		$badgesParser->parse( $badgeIds );
	}

	public function parseThrowsExceptionProvider() {
		return array(
			array( array( 'Q9000', 'P9000' ), true, 'P9000 is not an item ID.' ),
			array( array( 'Q9000', 'xyz' ), true, 'xyz is not a valid item ID.' ),
			array( array( 'Q9000', 'Q9001' ), false, 'badge is invalid' )
		);
	}

	private function getBadgeValidator( $isValid ) {
		$badgeValidator = $this->getMockBuilder( 'Wikibase\Badge\BadgeValidator' )
			->disableOriginalConstructor()
			->getMock();

		$badgeValidator->expects( $this->any() )
			->method( 'validate' )
			->will(
				$this->returnCallback( function() use( $isValid ) {
					if ( !$isValid ) {
						throw new BadgeException( 'badge-invalid', 'Q9000', 'badge is invalid' );
					}
				} )
			);

		return $badgeValidator;
	}

}
