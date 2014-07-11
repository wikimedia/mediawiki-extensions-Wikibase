<?php

namespace Wikibase\Tests\Repo;

use Wikibase\Repo\BadgeLookup;

/**
 * @covers Wikibase\Repo\BadgeLookup
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRepoTest
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class BadgeLookupTest extends \MediaWikiTestCase {

	private static $badgeTitles = array(
		'Q123' => 'foo',
		'Q456' => 'bar',
		'Q42' => 'Q42',
		'Q789' => 'baz',
		'Q100' => 'Q100',
	);

	public function testGetBadgeTitles() {
		$badgeLookup = new BadgeLookup(
			'en',
			self::$badgeTitles,
			$this->getEntityInfoBuilderMock()
		);

		$this->assertEquals(
			self::$badgeTitles,
			$badgeLookup->getBadgeTitles()
		);

		foreach ( self::$badgeTitles as $badgeId => $title ) {
			$this->assertEquals( $title, $badgeLookup->getBadgeTitle( $badgeId ) );
		}
	}

	private function getEntityInfoBuilderMock() {
		$entityInfoBuilder = $this->getMock( 'Wikibase\EntityInfoBuilder' );
		$badgeTitles = self::$badgeTitles;

		$entityInfoBuilder->expects( $this->any() )
			->method( 'buildEntityInfo' )
			->will( $this->returnValue( array() ) );

		$entityInfoBuilder->expects( $this->any() )
			->method( 'addTerms' )
			->will( $this->returnCallback( function ( array &$entityInfo ) use ( $badgeTitles ) {
				foreach ( $badgeTitles as $badgeId => $title ) {
					if ( $badgeId !== $title ) {
						$entityInfo[$badgeId] = array( 'labels' => array( 'en' => array( 'value' => $title ) ) );
					} else {
						$entityInfo[$badgeId] = array( 'labels' => array() );
					}
				}
			} ) );

		return $entityInfoBuilder;
	}

}
