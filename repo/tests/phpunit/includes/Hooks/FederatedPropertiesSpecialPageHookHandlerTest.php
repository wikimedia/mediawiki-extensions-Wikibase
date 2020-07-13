<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\Hooks;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Hooks\FederatedPropertiesSpecialPageHookHandler;

/**
 * @covers \Wikibase\Repo\Hooks\FederatedPropertiesSpecialPageHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesSpecialPageHookHandlerTest extends TestCase {

	public function testGivenFedPropsSettingEnabled_NewPropertySpecialPageDoesNotExist() {
		$mockList = [
			'NewProperty' => 'Create a new property special page',
			'OtherSpecialPAge' => 'Create some other Special page'
		];

		$hookHandler = new FederatedPropertiesSpecialPageHookHandler( true );
		$hookHandler->onSpecialPage_initList( $mockList );

		$this->assertfalse( isset( $mockList[ 'NewProperty' ] ) );
	}

	public function testGivenFedPropsSettingDisabled_doesNothing() {
		$mockList = [
			'NewProperty' => 'Create a new property special page',
			'OtherSpecialPAge' => 'Create some other Special page'
		];
		$expected = [
			'NewProperty' => 'Create a new property special page',
			'OtherSpecialPAge' => 'Create some other Special page'
		];

		$hookHandler = new FederatedPropertiesSpecialPageHookHandler( false );
		$hookHandler->onSpecialPage_initList( $mockList );//mockList should not change

		$this->assertEquals( $expected, $mockList );
	}
}
