<?php

namespace Wikibase\Test;

use FauxRequest;
use FormOptions;
use Wikibase\Client\Hooks\SpecialWatchlistQueryHandler;

/**
 * @covers Wikibase\Client\Hooks\SpecialWatchlistQueryHandler
 *
 * @group WikibaseClient
 * @group HookHandler
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SpecialWatchlistQueryHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider addWikibaseConditionsProvider
	 */
	public function testAddWikibaseConditions( $expected, $conds, $enhanced, $hideWikibase,
		$message
	) {
		$user = $this->getUser( $enhanced );

		$database = $this->getDatabase();
		$hookHandler = new SpecialWatchlistQueryHandler( $user, $database );

		$opts = new FormOptions();
		$opts->add( 'hideWikibase', $hideWikibase );

		$newConds = $hookHandler->addWikibaseConditions( new FauxRequest(), $conds, $opts );

		$this->assertEquals( $expected, $newConds, $message );
	}

	public function addWikibaseConditionsProvider() {
		$conds = array( "(rc_this_oldid=page_latest) OR rc_type = '3'" );

		$expectedHideConds = array_merge( $conds, array( 'rc_type != 5' ) );
		$expectedShowConds = array( "(rc_this_oldid=page_latest) OR rc_type IN (3,5)" );

		return array(
			array( $expectedHideConds, $conds, true, true, 'enhanced, hide wikibase opt' ),
			array( $expectedHideConds, $conds, true, false, 'enhanced, no hide wikibase opt' ),
			array( $expectedHideConds, $conds, false, true, 'not enhanced, hide wikibase opt' ),
			array( $expectedShowConds, $conds, false, false, 'not enhanced, show wikibase opt' )
		);
	}

	/**
	 * @param boolean $enhanced
	 */
	private function getUser( $enhanced ) {
		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'getOption' )
			->with( 'usenewrc' )
			->will( $this->returnCallback( function() use ( $enhanced ) {
				return $enhanced;
			} ) );

		return $user;
	}

	private function getDatabase() {
		$database = $this->getMockBuilder( 'DatabaseMysql' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'makeList' )
			->will( $this->returnCallback( function( $conds ) {
				if ( array_key_exists( 'rc_type', $conds ) ) {
					if ( $conds['rc_type'] === array( 3 ) ) {
						return "(rc_this_oldid=page_latest) OR rc_type = '3'";
					} else {
						return '(rc_this_oldid=page_latest) OR rc_type IN (3,5)';
					}
				}
			} ) );

		return $database;
	}
}
