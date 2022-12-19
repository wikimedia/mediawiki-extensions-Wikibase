<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use MediaWiki\MediaWikiServices;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers \Wikibase\Repo\Api\SetLabel
 * @covers \Wikibase\Repo\Api\ModifyTerm
 * @covers \Wikibase\Repo\Api\ModifyEntity
 *
 * @group Database
 * @group medium
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group BreakingTheSlownessBarrier
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class SetLabelTest extends ModifyTermTestCase {

	/**
	 * @var bool
	 */
	private static $hasSetup;

	protected function setUp(): void {
		parent::setUp();

		self::$testAction = 'wbsetlabel';

		if ( !isset( self::$hasSetup ) ) {
			$this->initTestEntities( [ 'Empty' ] );
		}
		self::$hasSetup = true;
	}

	public function testGetAllowedParams_listsItemsAndProperties() {
		list( $result, ) = $this->doApiRequest(
			[
				'action' => 'paraminfo',
				'modules' => self::$testAction,
			]
		);
		$apiParams = $result['paraminfo']['modules'][0]['parameters'];

		$completedAssertions = false;
		foreach ( $apiParams as $paramDetails ) {
			if ( $paramDetails['name'] === 'new' ) {
				$this->assertContains( 'item', $paramDetails['type'] );
				$this->assertContains( 'property', $paramDetails['type'] );
				$completedAssertions = true;
			}
		}

		if ( !$completedAssertions ) {
			$this->fail( 'Failed to find and verify \'new\' parameter docs for wbsetlabel' );
		}
	}

	public function testSetLabelOnRedirectRevision() {
		// Create an item
		list( $result, , ) = $this->doApiRequestWithToken( $this->getCreateItemAndSetLabelRequestParams() );
		$id = $result['entity']['id'];

		// Add some data to it and record its base rev
		$setupParams = [
			'action' => 'wbeditentity',
			'id' => $id,
			'data' => '{"descriptions":{"en":{"language":"en","value":"SetLabelOnRedirectRevision2"}}}',
		];
		list( $result, , ) = $this->doApiRequestWithToken( $setupParams );
		$baserevId = $result['entity']['lastrevid'];

		// Create another item as future target of redirect
		list( $result, , ) = $this->doApiRequestWithToken( $this->getCreateItemAndSetLabelRequestParams() );
		$newId = $result['entity']['id'];

		// Clear the first item
		$setupParams = [
			'action' => 'wbeditentity',
			'id' => $id,
			'clear' => true,
			'data' => '{}',
		];
		$this->doApiRequestWithToken( $setupParams );

		// Redirect the first item to the second item
		$setupParams = [
			'action' => 'wbcreateredirect',
			'from' => $id,
			'to' => $newId,
		];
		$this->doApiRequestWithToken( $setupParams );

		// Try to set label on the now-redirect item with baserevid of the non-redirect content
		$params = [
			'action' => 'wbsetlabel',
			'id' => $id,
			'language' => 'en',
			'value' => 'a different label',
			'baserevid' => $baserevId,
		];
		$expectedException = [ 'type' => ApiUsageException::class, 'code' => 'unresolved-redirect' ];
		$this->doTestQueryExceptions( $params, $expectedException );
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
	public function testSetLabelExceptions( $params, $expected, $token = true ) {
		self::doTestSetTermExceptions( $params, $expected, $token );
	}

	public function testSetLabelWithTag() {
		$this->assertCanTagSuccessfulRequest( $this->getCreateItemAndSetLabelRequestParams() );
	}

	public function testUserCanEditWhenTheyHaveSufficientPermission() {
		$userWithAllPermissions = $this->createUserWithGroup( 'all-permission' );

		$this->setMwGlobals( 'wgGroupPermissions', [
			'all-permission' => [ 'item-term' => true ],
			'*' => [ 'read' => true, 'edit' => true, 'writeapi' => true ],
		] );

		$newItem = $this->createItemUsing( $userWithAllPermissions );

		list( $result, ) = $this->doApiRequestWithToken(
			$this->getSetLabelRequestParams( $newItem->getId() ),
			null,
			$userWithAllPermissions
		);

		$this->assertSame( 1, $result['success'] );
	}

	public function testUserCannotSetLabelWhenTheyLackPermission() {
		$this->markTestSkipped( 'Disabled due to flakiness JDF 2019-03-19 T218378' );

		$userWithInsufficientPermissions = $this->createUserWithGroup( 'no-permission' );
		$userWithAllPermissions = $this->createUserWithGroup( 'all-permission' );

		$this->setMwGlobals( 'wgGroupPermissions', [
			'no-permission' => [ 'item-term' => false ],
			'all-permission' => [ 'item-term' => true ],
			'*' => [ 'read' => true, 'edit' => true, 'writeapi' => true ],
		] );

		// And an item
		$newItem = $this->createItemUsing( $userWithAllPermissions );

		// Then the request is denied
		$expected = [
			'type' => ApiUsageException::class,
			'code' => 'permissiondenied',
		];

		$this->doTestQueryExceptions(
			$this->getSetLabelRequestParams( $newItem->getId() ),
			$expected,
			$userWithInsufficientPermissions
		);
	}

	public function testUserCanCreateItemWithLabelWhenTheyHaveSufficientPermissions() {
		$userWithAllPermissions = $this->createUserWithGroup( 'all-permission' );

		$this->setMwGlobals( 'wgGroupPermissions', [
			'all-permission' => [ 'item-term' => true, 'createpage' => true ],
			'*' => [ 'read' => true, 'edit' => true, 'writeapi' => true ],
		] );

		list( $result, ) = $this->doApiRequestWithToken(
			$this->getCreateItemAndSetLabelRequestParams(),
			null,
			$userWithAllPermissions
		);

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( 'a label', $result['entity']['labels']['en']['value'] );
	}

	public function testUserCannotCreateItemWhenTheyLackPermission() {
		$userWithInsufficientPermissions = $this->createUserWithGroup( 'no-permission' );

		$this->setMwGlobals( 'wgGroupPermissions', [
			'no-permission' => [ 'createpage' => false ],
			'*' => [ 'read' => true, 'edit' => true, 'item-term' => true, 'writeapi' => true ],
		] );

		MediaWikiServices::getInstance()->resetServiceForTesting( 'PermissionManager' );

		// Then the request is denied
		$expected = [
			'type' => ApiUsageException::class,
			'code' => 'permissiondenied',
		];

		$this->doTestQueryExceptions(
			$this->getCreateItemAndSetLabelRequestParams(),
			$expected,
			$userWithInsufficientPermissions
		);
	}

	/**
	 * @param User $user
	 *
	 * @return Item
	 */
	private function createItemUsing( User $user ) {
		$store = $this->getEntityStore();

		$itemRevision = $store->saveEntity( new Item(), 'SetSiteLinkTest', $user, EDIT_NEW );
		return $itemRevision->getEntity();
	}

	/**
	 * @param string $groupName
	 *
	 * @return User
	 */
	private function createUserWithGroup( $groupName ) {
		return $this->getTestUser( [ 'wbeditor', $groupName ] )->getUser();
	}

	private function getCreateItemAndSetLabelRequestParams() {
		return [
			'action' => 'wbsetlabel',
			'new' => 'item',
			'language' => 'en',
			'value' => 'a label',
		];
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
