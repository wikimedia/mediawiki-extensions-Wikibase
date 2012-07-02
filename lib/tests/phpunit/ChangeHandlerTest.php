<?php

namespace Wikibase\Test;
use Wikibase\ChangeHandler as ChangeHandler;
use Wikibase\ItemObject as ItemObject;

/**
 * Tests for the Wikibase\ChangeHandler class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ChangeHandlerTest extends \MediaWikiTestCase {

	public function testSingleton() {
		$this->assertInstanceOf( '\Wikibase\ChangeHandler', ChangeHandler::singleton() );
		$this->assertTrue( ChangeHandler::singleton() === ChangeHandler::singleton() );
	}

	public function changeProvider() {
		$itemCreation = \Wikibase\ItemCreation::newFromItem( ItemObject::newEmpty() );
		$itemDeletion = \Wikibase\ItemDeletion::newFromItem( ItemObject::newEmpty() );

		return array(
			array(),
			array( $itemCreation ),
			array( $itemDeletion ),
			array( $itemCreation, $itemDeletion ),
		);
	}

	/**
	 * @dataProvider changeProvider
	 */
	public function testHandleChanges() {
		$changes = func_get_args();

		global $wgHooks;

		$wgHooksOriginal = $wgHooks;

		global $changeHandlerHookCallCount, $changeHandlerBeforeHookCallCount, $changeHandlerAfterHookCallCount;
		$changeHandlerHookCallCount = 0;
		$changeHandlerBeforeHookCallCount = 0;
		$changeHandlerAfterHookCallCount = 0;

		$wgHooks['WikibasePollHandle'] = array( function( \Wikibase\Change $change ) {
			global $changeHandlerHookCallCount;
			$changeHandlerHookCallCount++;
			return true;
		} );

		$wgHooks['WikibasePollBeforeHandle'] = array( function( array $changes ) {
			global $changeHandlerBeforeHookCallCount;
			$changeHandlerBeforeHookCallCount++;
			return true;
		} );

		$wgHooks['WikibasePollAfterHandle'] = array( function( array $changes ) {
			global $changeHandlerAfterHookCallCount;
			$changeHandlerAfterHookCallCount++;
			return true;
		} );

		ChangeHandler::singleton()->handleChanges( $changes );

		$this->assertEquals( count( $changes ), $changeHandlerHookCallCount );
		$this->assertEquals( 1, $changeHandlerBeforeHookCallCount );
		$this->assertEquals( 1, $changeHandlerAfterHookCallCount );

		$wgHooks = $wgHooksOriginal;
	}

}
