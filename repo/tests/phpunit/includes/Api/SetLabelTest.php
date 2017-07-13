<?php

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Api\SetLabel
 * @covers Wikibase\Repo\Api\ModifyTerm
 * @covers Wikibase\Repo\Api\ModifyEntity
 *
 * @group Database
 * @group medium
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group BreakingTheSlownessBarrier
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class SetLabelTest extends ModifyTermTestCase {

	private static $hasSetup;

	protected function setUp() {
		parent::setUp();

		self::$testAction = 'wbsetlabel';

		if ( !isset( self::$hasSetup ) ) {
			$this->initTestEntities( [ 'Empty' ] );
		}
		self::$hasSetup = true;
	}

	/**
	 * @dataProvider provideData
	 */
	public function testSetLabel( $params, $expected ) {
		self::doTestSetTerm( 'labels', $params, $expected );
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testSetLabelExceptions( $params, $expected ) {
		self::doTestSetTermExceptions( $params, $expected );
	}

	public function testUserCanEditWhenTheyHaveSufficientPermission() {
		$userWithAllPermissions = $this->createUserWithGroup( 'all-permission' );

		$this->setMwGlobals( 'wgGroupPermissions', [
			'all-permission' => [ 'item-term' => true, ],
			'*' => [ 'read' => true, 'edit' => true, 'writeapi' => true ]
		] );

		$newItem = $this->createItemUsing( $userWithAllPermissions );

		list ( $result, ) = $this->doApiRequestWithToken(
			$this->getSetLabelRequestParams( $newItem->getId() ),
			null,
			$userWithAllPermissions
		);

		$this->assertEquals( 1, $result['success'] );
	}

	public function testUserCannotSetLabelWhenTheyLackPermission() {
		$userWithInsufficientPermissions = $this->createUserWithGroup( 'no-permission' );
		$userWithAllPermissions = $this->createUserWithGroup( 'all-permission' );

		$this->setMwGlobals( 'wgGroupPermissions', [
			'no-permission' => [ 'item-term' => false ],
			'all-permission' => [ 'item-term' => true, ],
			'*' => [ 'read' => true, 'edit' => true, 'writeapi' => true ]
		] );

		// And an item
		$newItem = $this->createItemUsing( $userWithAllPermissions );

		// Then the request is denied
		$expected = [
			'type' => ApiUsageException::class,
			'code' => 'permissiondenied'
		];

		$this->doTestQueryExceptions(
			$this->getSetLabelRequestParams( $newItem->getId() ),
			$expected,
			$userWithInsufficientPermissions
		);
	}

	/**
	 * @param User $user
	 * @return Item
	 */
	private function createItemUsing( User $user ) {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$itemRevision = $store->saveEntity( new Item(), 'SetSiteLinkTest', $user, EDIT_NEW );
		return $itemRevision->getEntity();
	}

	private function createUserWithGroup( $groupName ) {
		$user = $this->createTestUser()->getUser();
		$user->addGroup( $groupName );
		return $user;

	}

	private function getSetLabelRequestParams( ItemId $id ) {
		return [
			'action' => 'wbsetlabel',
			'id' => $id->getSerialization(),
			'language' => 'en',
			'value' => 'other label',
		];
	}

}
