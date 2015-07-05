<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\EditEntityFactory;

/**
 * @covers Wikibase\EditEntityFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GPLv2+
 * @author Adam Shorland
 */
class EditEntityFactoryTest extends \PHPUnit_Framework_TestCase {

	private function newEditEntityFactory() {
		$titleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$entityLookup = $this->getMock( 'Wikibase\Lib\Store\EntityRevisionLookup' );
		$entityStore = $this->getMock( 'Wikibase\Lib\Store\EntityStore' );
		$permissionChecker = $this->getMock( 'Wikibase\Repo\Store\EntityPermissionChecker' );
		$editFilterHookRunner = $this->getMockBuilder( 'Wikibase\Repo\Hooks\EditFilterHookRunner' )
			->disableOriginalConstructor()
			->getMock();

		return new EditEntityFactory(
			$titleLookup,
			$entityLookup,
			$entityStore,
			$permissionChecker,
			$editFilterHookRunner
		);
	}

	private function getMockUser() {
		return $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testGetEntityLoadHelper() {
		$factory = $this->newEditEntityFactory();

		$editEntity = $factory->newEditEntity( $this->getMockUser(), new Item() );
		$this->assertInstanceOf( 'Wikibase\EditEntity', $editEntity );
	}

}
