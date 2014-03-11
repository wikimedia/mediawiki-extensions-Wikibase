<?php

namespace Wikibase\Test;

use FauxRequest;
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

	public function testHandleAddWikibaseConditions() {
		$user = $this->getUser( true );
		$db = wfGetDB( DB_SLAVE ); //$this->getDatabase();

		$hookHandler = new SpecialWatchlistQueryHandler( $user, $db );

		$request = new FauxRequest();
		$conds = array();
		$opts = array();

		$result = $hookHandler->handleAddWikibaseConditions( $request, $conds, $opts );

		$this->assertTrue( true );
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
			->will( $this->returnValue( 1 ) ); //Callback( function() { return 1; } ) );

		return $user;
	}

	private function getDatabase() {
		$database = $this->getMockBuilder( 'DatabaseBase' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'makeList' )
			->will( $this->returnValue( 'list' ) );

		return $database;
	}

}
