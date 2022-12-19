<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use MediaWiki\MediaWikiServices;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers \Wikibase\Repo\Api\SetDescription
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
class SetDescriptionTest extends ModifyTermTestCase {

	/**
	 * @var bool
	 */
	private static $hasSetup;

	protected function setUp(): void {
		parent::setUp();

		self::$testAction = 'wbsetdescription';

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

	/**
	 * @dataProvider provideData
	 */
	public function testSetDescription( $params, $expected ) {
		self::doTestSetTerm( 'descriptions', $params, $expected );
	}

	public function testSetDescriptionWithTag() {
		$this->assertCanTagSuccessfulRequest( $this->getCreateItemAndSetDescriptionRequestParams() );
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testSetDescriptionExceptions( $params, $expected, $token = true ) {
		self::doTestSetTermExceptions( $params, $expected, $token );
	}

	public function testUserCanSetDescriptionWhenTheyHaveSufficientPermission() {
		$userWithAllPermissions = $this->createUserWithGroup( 'all-permission' );

		$this->setMwGlobals( 'wgGroupPermissions', [
			'all-permission' => [ 'item-term' => true ],
			'*' => [ 'read' => true, 'edit' => true, 'writeapi' => true ],
		] );

		$newItem = $this->createItemUsing( $userWithAllPermissions );

		list( $result, ) = $this->doApiRequestWithToken(
			$this->getSetDescriptionRequestParams( $newItem->getId() ),
			null,
			$userWithAllPermissions
		);

		$this->assertSame( 1, $result['success'] );
	}

	public function testUserCannotSetDescriptionWhenTheyLackPermission() {
		$userWithInsufficientPermissions = $this->createUserWithGroup( 'no-permission' );
		$userWithAllPermissions = $this->createUserWithGroup( 'all-permission' );

		$this->setMwGlobals( 'wgGroupPermissions', [
			'no-permission' => [ 'item-term' => false ],
			'all-permission' => [ 'item-term' => true ],
			'*' => [ 'read' => true, 'edit' => true, 'writeapi' => true ],
		] );

		MediaWikiServices::getInstance()->resetServiceForTesting( 'PermissionManager' );

		// And an item
		$newItem = $this->createItemUsing( $userWithAllPermissions );

		// Then the request is denied
		$expected = [
			'type' => ApiUsageException::class,
			'code' => 'permissiondenied',
		];

		MediaWikiServices::getInstance()->getPermissionManager()->invalidateUsersRightsCache(
			$userWithAllPermissions
		);
		MediaWikiServices::getInstance()->getPermissionManager()->invalidateUsersRightsCache(
			$userWithInsufficientPermissions
		);

		$this->doTestQueryExceptions(
			$this->getSetDescriptionRequestParams( $newItem->getId() ),
			$expected,
			$userWithInsufficientPermissions
		);
	}

	public function testUserCanCreateItemWithDescriptionWhenTheyHaveSufficientPermissions() {
		$userWithAllPermissions = $this->createUserWithGroup( 'all-permission' );

		$this->setMwGlobals( 'wgGroupPermissions', [
			'all-permission' => [ 'item-term' => true, 'createpage' => true ],
			'*' => [ 'read' => true, 'edit' => true, 'writeapi' => true ],
		] );

		list( $result, ) = $this->doApiRequestWithToken(
			$this->getCreateItemAndSetDescriptionRequestParams(),
			null,
			$userWithAllPermissions
		);

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( 'some description', $result['entity']['descriptions']['en']['value'] );
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
			$this->getCreateItemAndSetDescriptionRequestParams(),
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

	private function getSetDescriptionRequestParams( ItemId $id ) {
		return [
			'action' => 'wbsetdescription',
			'id' => $id->getSerialization(),
			'language' => 'en',
			'value' => 'some description',
		];
	}

	private function getCreateItemAndSetDescriptionRequestParams() {
		return [
			'action' => 'wbsetdescription',
			'new' => 'item',
			'language' => 'en',
			'value' => 'some description',
		];
	}

}
