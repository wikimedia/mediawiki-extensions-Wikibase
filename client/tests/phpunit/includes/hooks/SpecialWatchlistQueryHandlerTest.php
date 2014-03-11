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
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SpecialWatchlistQueryHandlerTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider addWikibaseConditionsProvider
	 */
	public function testAddWikibaseConditions( $expected, $conds, $enhanced, $hideWikibase, $message ) {
		$user = $this->getUser( $enhanced );

		$hookHandler = new SpecialWatchlistQueryHandler( $user, $this->db );

		$opts = new FormOptions();
		$opts->add( 'hideWikibase', $hideWikibase );

		$newConds = $hookHandler->addWikibaseConditions( new FauxRequest(), $conds, $opts );

		$this->assertEquals( $expected, $newConds, $message );
	}

	public function addWikibaseConditionsProvider() {
		$conds = array(
			"rc_timestamp > '20140311225259'",
			"(rc_this_oldid=page_latest) OR rc_type = '3'"
		);

		$expectedForHideWikibase = array_merge( $conds, array( "rc_type != 5" ) );

		// note: DatabaseBase::makeList adds extra space at end of IN array condition
		$expectedForShowWikibase = array(
			"rc_timestamp > '20140311225259'",
			"(rc_this_oldid=page_latest) OR rc_type IN ('3','5') "
		);

		return array(
			array( $expectedForHideWikibase, $conds, true, true, 'enhanced, hide wikibase opt' ),
			array( $expectedForHideWikibase, $conds, true, false, 'enhanced, no hide wikibase opt' ),
			array( $expectedForHideWikibase, $conds, false, true, 'not enhanced, hide wikibase opt' ),
			array( $expectedForShowWikibase, $conds, false, false, 'not enhanced, show wikibase opt' )
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

}
