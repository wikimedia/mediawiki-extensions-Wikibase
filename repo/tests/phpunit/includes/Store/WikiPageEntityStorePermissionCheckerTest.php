<?php

namespace Wikibase\Repo\Tests\Store;

use InvalidArgumentException;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleValue;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\WikiPageEntityStorePermissionChecker;

/**
 * @covers \Wikibase\Repo\Store\WikiPageEntityStorePermissionChecker
 *
 * @group Database
 * @group Wikibase
 * @group medium
 *
 * @license GPL-2.0-or-later
 */
class WikiPageEntityStorePermissionCheckerTest extends MediaWikiIntegrationTestCase {

	private const EXISTING_ITEM_ID = 'Q2';
	private const NON_EXISTING_ITEM_ID = 'Q3';
	private const EXISTING_PROPERTY_ID = 'P2';
	private const NON_EXISTING_PROPERTY_ID = 'P3';

	/**
	 * @dataProvider provideExistingEntities
	 */
	public function testEditPermissionsAreRequiredToEditExistingEntity( EntityDocument $existingEntity ) {
		$this->anyUserHasPermissions( [ 'edit' => true ] );

		$this->assertUserIsAllowedTo( EntityPermissionChecker::ACTION_EDIT, $existingEntity );
	}

	public static function provideExistingEntities(): iterable {
		return [
			'existing item' => [ self::getExistingItem() ],
			'existing property' => [ self::getExistingProperty() ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndPermissionsThatDisallowEdit
	 */
	public function testEditPermissionsAreRequiredToEditExistingEntity_failures(
		EntityDocument $existingEntity,
		array $permissions
	) {
		$this->anyUserHasPermissions( $permissions );

		$this->assertItIsForbiddenForUserTo( EntityPermissionChecker::ACTION_EDIT, $existingEntity );
	}

	public static function provideExistingEntitiesAndPermissionsThatDisallowEdit(): iterable {
		return [
			'existing item, no edit permission' => [ self::getExistingItem(), [ 'edit' => false ] ],
			'existing item, no read permission' => [ self::getExistingItem(), [ 'read' => false, 'edit' => true ] ],
			'existing property, no edit permission' => [ self::getExistingProperty(), [ 'edit' => false ] ],
			'existing property, no read permission' => [ self::getExistingProperty(), [ 'read' => false, 'edit' => true ] ],
		];
	}

	/**
	 * @dataProvider provideNonExistingEntitiesAndPermissionsThatAllowEditingTerms
	 */
	public function testAllRequiredPermissionsAreNeededToEditNonExistingEntity(
		EntityDocument $nonExistingEntity,
		array $groupPermissions
	) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertUserIsAllowedTo( EntityPermissionChecker::ACTION_EDIT, $nonExistingEntity );
	}

	/**
	 * @dataProvider provideNonExistingEntitiesAndPermissionsThatDisallowEdit
	 */
	public function testAllRequiredPermissionsAreNeededToEditNonExistingEntity_failures(
		EntityDocument $nonExistingentity,
		array $groupPermissions
	) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertItIsForbiddenForUserTo( EntityPermissionChecker::ACTION_EDIT, $nonExistingentity );
	}

	public static function provideNonExistingEntitiesAndPermissionsThatDisallowEdit(): iterable {
		return [
			'non-existing item, no createpage permission' => [
				self::getNonExistingItem(),
				[ 'createpage' => false ],
			],
			'non-existing item, no edit permission' => [
				self::getNonExistingItem(),
				[ 'edit' => false, 'createpage' => true ],
			],
			'non-existing item, no read permission' => [
				self::getNonExistingItem(),
				[ 'read' => false, 'edit' => true, 'createpage' => true ],
			],
			'non-existing item (null ID), no createpage permission' => [
				self::getNonExistingItemWithNullId(),
				[ 'createpage' => false ],
			],
			'non-existing item (null ID), no edit permission' => [
				self::getNonExistingItemWithNullId(),
				[ 'edit' => false, 'createpage' => true ],
			],
			'non-existing item (null ID), no read permission' => [
				self::getNonExistingItemWithNullId(),
				[ 'read' => false, 'edit' => true, 'createpage' => true ],
			],
			'non-existing property, no property-create permission' => [
				self::getNonExistingProperty(),
				[ 'createpage' => true, 'property-create' => false ],
			],
			'non-existing property, no createpage permission' => [
				self::getNonExistingProperty(),
				[ 'createpage' => false, 'property-create' => true ],
			],
			'non-existing property, no createpage nor property-create permission' => [
				self::getNonExistingProperty(),
				[ 'createpage' => false, 'property-create' => false ],
			],
			'non-existing property, no edit permission' => [
				self::getNonExistingProperty(),
				[ 'edit' => false, 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property, no read permission' => [
				self::getNonExistingProperty(),
				[ 'read' => false, 'edit' => true, 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property (null ID), no property-create permission' => [
				self::getNonExistingPropertyWithNullId(),
				[ 'createpage' => true, 'property-create' => false ],
			],
			'non-existing property (null ID), no createpage permission' => [
				self::getNonExistingPropertyWithNullId(),
				[ 'createpage' => false, 'property-create' => true ],
			],
			'non-existing property (null ID), no createpage nor property-create permission' => [
				self::getNonExistingPropertyWithNullId(),
				[ 'createpage' => false, 'property-create' => false ],
			],
			'non-existing property (null ID), no edit permission' => [
				self::getNonExistingPropertyWithNullId(),
				[ 'edit' => false, 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property (null ID), no read permission' => [
				self::getNonExistingPropertyWithNullId(),
				[ 'read' => false, 'edit' => true, 'createpage' => true, 'property-create' => true ],
			],
		];
	}

	/**
	 * @dataProvider provideAllEntities
	 */
	public function testReadPermissionsAreNeededToReadAnEntity( EntityDocument $entity ) {
		$this->anyUserHasPermissions( [ 'read' => true ] );

		$this->assertUserIsAllowedTo( EntityPermissionChecker::ACTION_READ, $entity );
	}

	/**
	 * @dataProvider provideAllEntities
	 */
	public function testReadPermissionsAreNeededToReadAnEntity_failures( EntityDocument $entity ) {
		$this->anyUserHasPermissions( [ 'read' => false ] );

		$this->assertItIsForbiddenForUserTo( EntityPermissionChecker::ACTION_READ, $entity );
	}

	public static function provideAllEntities(): iterable {
		return [
			'existing item' => [ self::getExistingItem() ],
			'existing property' => [ self::getExistingProperty() ],
			'non-existing item' => [ self::getNonExistingItem() ],
			'non-existing item (null ID)' => [ self::getNonExistingItemWithNullId() ],
			'non-existing property' => [ self::getNonExistingProperty() ],
			'non-existing property (null ID)' => [ self::getNonExistingPropertyWithNullId() ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndPermissionsThatAllowMerge
	 */
	public function testAllRequiredPermissionsAreNeededToMergeEntity( EntityDocument $entity, array $groupPermissions ) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertUserIsAllowedTo( EntityPermissionChecker::ACTION_MERGE, $entity );
	}

	public static function provideExistingEntitiesAndPermissionsThatAllowMerge(): iterable {
		return [
			'existing item, edit and item-merge permissions' => [
				self::getExistingItem(),
				[ 'edit' => true, 'item-merge' => true ],
			],
			// TODO: should this be even tested? Or should it return false/throw exception for properties?
			'existing property, edit permission' => [
				self::getExistingProperty(),
				[ 'edit' => true ],
			],
			'existing property, item-merge permission is irrelevant' => [
				self::getExistingProperty(),
				[ 'edit' => true, 'item-merge' => false ],
			],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndPermissionsThatDisallowMerge
	 */
	public function testAllRequiredPermissionsAreNeededToMergeEntity_failures( EntityDocument $entity, array $groupPermissions ) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertItIsForbiddenForUserTo( EntityPermissionChecker::ACTION_MERGE, $entity );
	}

	public static function provideExistingEntitiesAndPermissionsThatDisallowMerge(): iterable {
		return [
			'existing item, no item-merge permissions' => [
				self::getExistingItem(),
				[ 'edit' => true, 'item-merge' => false ],
			],
			'existing item, no edit permissions' => [
				self::getExistingItem(),
				[ 'edit' => false, 'item-merge' => true ],
			],
			'existing item, no edit nor item-merge permissions' => [
				self::getExistingItem(),
				[ 'edit' => false, 'item-merge' => false ],
			],
			'existing item, no read permissions' => [
				self::getExistingItem(),
				[ 'read' => false, 'edit' => true, 'item-merge' => true ],
			],
			// TODO: should this be even tested? Or should it return false/throw exception for properties?
			'existing property, no edit permissions' => [
				self::getExistingProperty(),
				[ 'edit' => false ],
			],
			'existing property, no read permissions' => [
				self::getExistingProperty(),
				[ 'read' => false, 'edit' => true ],
			],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndPermissionsThatAllowRedirect
	 */
	public function testAllRequiredPermissionsAreNeededToRedirectEntity( EntityDocument $entity, array $groupPermissions ) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertUserIsAllowedTo( EntityPermissionChecker::ACTION_REDIRECT, $entity );
	}

	public static function provideExistingEntitiesAndPermissionsThatAllowRedirect(): iterable {
		return [
			'existing item, edit and item-redirect permissions' => [
				self::getExistingItem(),
				[ 'edit' => true, 'item-redirect' => true ],
			],
			// TODO: should this be even tested? Or should it return false/throw exception for properties?
			'existing property, edit permission' => [
				self::getExistingProperty(),
				[ 'edit' => true ],
			],
			'existing property, item-redirect permission is irrelevant' => [
				self::getExistingProperty(),
				[ 'edit' => true, 'item-redirect' => false ],
			],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndPermissionsThatDisallowRedirect
	 */
	public function testAllRequiredPermissionsAreNeededToRedirectEntity_failures(
		EntityDocument $entity,
		array $groupPermissions
	) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertItIsForbiddenForUserTo( EntityPermissionChecker::ACTION_REDIRECT, $entity );
	}

	public static function provideExistingEntitiesAndPermissionsThatDisallowRedirect(): iterable {
		return [
			'existing item, no item-redirect permission' => [
				self::getExistingItem(),
				[ 'edit' => true, 'item-redirect' => false ],
			],
			'existing item, no edit permission' => [
				self::getExistingItem(),
				[ 'edit' => false, 'item-redirect' => true ],
			],
			'existing item, no edit nor item-redirect permission' => [
				self::getExistingItem(),
				[ 'edit' => false, 'item-redirect' => false ],
			],
			'existing item, no read permission' => [
				self::getExistingItem(),
				[ 'read' => false, 'edit' => true, 'item-redirect' => true ],
			],
			// TODO: should this be even tested? Or should it return false/throw exception for properties?
			'existing property, no edit permission' => [
				self::getExistingProperty(),
				[ 'edit' => false ],
			],
			'existing property, no read permission' => [
				self::getExistingProperty(),
				[ 'read' => false, 'edit' => true ],
			],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndPermissionsThatAllowEditingTerms
	 */
	public function testAllRequiredPermissionsAreNeededToEditTerms( EntityDocument $entity, array $groupPermissions ) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertUserIsAllowedTo( EntityPermissionChecker::ACTION_EDIT_TERMS, $entity );
	}

	public static function provideExistingEntitiesAndPermissionsThatAllowEditingTerms(): iterable {
		return [
			'existing item, edit and item-term permissions' => [
				self::getExistingItem(),
				[ 'edit' => true, 'item-term' => true ],
			],
			'existing property, edit and property-term permissions' => [
				self::getExistingProperty(),
				[ 'edit' => true, 'property-term' => true ],
			],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndPermissionsThatDisallowEditingTerms
	 */
	public function testAllRequiredPermissionsAreNeededToEditTerms_failures( EntityDocument $entity, array $groupPermissions ) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertItIsForbiddenForUserTo( EntityPermissionChecker::ACTION_EDIT_TERMS, $entity );
	}

	public static function provideExistingEntitiesAndPermissionsThatDisallowEditingTerms(): iterable {
		return [
			'existing item, no item-term permission' => [
				self::getExistingItem(),
				[ 'edit' => true, 'item-term' => false ],
			],
			'existing item, no edit permission' => [
				self::getExistingItem(),
				[ 'edit' => false, 'item-term' => true ],
			],
			'existing item, no edit nor item-term permission' => [
				self::getExistingItem(),
				[ 'edit' => false, 'item-term' => false ],
			],
			'existing item, no read permission' => [
				self::getExistingItem(),
				[ 'read' => false, 'edit' => true, 'item-term' => true ],
			],
			'existing property, no property-term permission' => [
				self::getExistingProperty(),
				[ 'edit' => true, 'property-term' => false ],
			],
			'existing property, no edit permission' => [
				self::getExistingProperty(),
				[ 'edit' => false, 'property-term' => true ],
			],
			'existing property, no edit nor property-term permission' => [
				self::getExistingProperty(),
				[ 'edit' => false, 'property-term' => false ],
			],
			'existing property, no read permission' => [
				self::getExistingProperty(),
				[ 'read' => false, 'edit' => true, 'property-term' => true ],
			],
		];
	}

	/**
	 * @dataProvider provideNonExistingEntitiesAndPermissionsThatAllowEditingTerms
	 */
	public function testAllRequiredPermissionsAreNeededToEditTermsOfNonExistingEntity(
		EntityDocument $nonExistingEntity,
		array $groupPermissions
	) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertUserIsAllowedTo( EntityPermissionChecker::ACTION_EDIT_TERMS, $nonExistingEntity );
	}

	public static function provideNonExistingEntitiesAndPermissionsThatAllowEditingTerms(): iterable {
		return [
			'non-existing item, createpage permission' => [
				self::getNonExistingItem(),
				[ 'createpage' => true ],
			],
			'non-existing item (null ID), createpage permission' => [
				self::getNonExistingItemWithNullId(),
				[ 'createpage' => true ],
			],
			'non-existing property, createpage and property-create permission' => [
				self::getNonExistingProperty(),
				[ 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property (null ID), createpage and property-create permission' => [
				self::getNonExistingPropertyWithNullId(),
				[ 'createpage' => true, 'property-create' => true ],
			],
		];
	}

	/**
	 * @dataProvider provideNonExistingEntitiesAndPermissionsThatDisallowEditingTerms
	 */
	public function testAllRequiredPermissionsAreNeededToEditTermsOfNonExistingEntity_failures(
		EntityDocument $nonExistingEntity,
		array $groupPermissions
	) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertItIsForbiddenForUserTo( EntityPermissionChecker::ACTION_EDIT_TERMS, $nonExistingEntity );
	}

	public static function provideNonExistingEntitiesAndPermissionsThatDisallowEditingTerms(): iterable {
		return [
			'non-existing item, no createpage permission' => [
				self::getNonExistingItem(),
				[ 'createpage' => false ],
			],
			'non-existing item, no item-term permission' => [
				self::getNonExistingItem(),
				[ 'item-term' => false, 'createpage' => true ],
			],
			'non-existing item, no edit permission' => [
				self::getNonExistingItem(),
				[ 'edit' => false, 'item-term' => true, 'createpage' => true ],
			],
			'non-existing item, no read permission' => [
				self::getNonExistingItem(),
				[ 'read' => false, 'edit' => true, 'item-term' => true, 'createpage' => true ],
			],
			'non-existing item (null ID), no createpage permission' => [
				self::getNonExistingItemWithNullId(),
				[ 'createpage' => false ],
			],
			'non-existing item (null ID), no item-term permission' => [
				self::getNonExistingItemWithNullId(),
				[ 'item-term' => false, 'createpage' => true ],
			],
			'non-existing item (null ID), no edit permission' => [
				self::getNonExistingItemWithNullId(),
				[ 'edit' => false, 'item-term' => true, 'createpage' => true ],
			],
			'non-existing item (null ID), no read permission' => [
				self::getNonExistingItemWithNullId(),
				[ 'read' => false, 'edit' => true, 'item-term' => true, 'createpage' => true ],
			],
			'non-existing property, no property-create permission' => [
				self::getNonExistingProperty(),
				[ 'createpage' => true, 'property-create' => false ],
			],
			'non-existing property, no createpage permission' => [
				self::getNonExistingProperty(),
				[ 'createpage' => false, 'property-create' => true ],
			],
			'non-existing property, no createpage nor property-create permission' => [
				self::getNonExistingProperty(),
				[ 'createpage' => false, 'property-create' => false ],
			],
			'non-existing property, no property-term permission' => [
				self::getNonExistingProperty(),
				[ 'property-term' => false, 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property, no edit permission' => [
				self::getNonExistingProperty(),
				[ 'edit' => false, 'property-term' => true, 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property, no read permission' => [
				self::getNonExistingProperty(),
				[ 'read' => false, 'edit' => true, 'property-term' => true, 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property (null ID), no property-create permission' => [
				self::getNonExistingPropertyWithNullId(),
				[ 'createpage' => true, 'property-create' => false ],
			],
			'non-existing property (null ID), no createpage permission' => [
				self::getNonExistingPropertyWithNullId(),
				[ 'createpage' => false, 'property-create' => true ],
			],
			'non-existing property (null ID), no createpage nor property-create permission' => [
				self::getNonExistingPropertyWithNullId(),
				[ 'createpage' => false, 'property-create' => false ],
			],
			'non-existing property (null ID), no property-term permission' => [
				self::getNonExistingPropertyWithNullId(),
				[ 'property-term' => false, 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property (null ID), no edit permission' => [
				self::getNonExistingPropertyWithNullId(),
				[ 'edit' => false, 'property-term' => true, 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property (null ID), no read permission' => [
				self::getNonExistingPropertyWithNullId(),
				[ 'read' => false, 'edit' => true, 'property-term' => true, 'createpage' => true, 'property-create' => true ],
			],
		];
	}

	public function testGivenUnknownPermission_getPermissionStatusForEntityThrowsException() {
		$checker = $this->getPermissionChecker();

		$this->expectException( InvalidArgumentException::class );

		$checker->getPermissionStatusForEntity(
			$this->getTestUser()->getUser(),
			'turn-into-an-elephant',
			$this->getExistingItem()
		);
	}

	public function testGivenUnknownPermission_getPermissionStatusForEntityIdThrowsException() {
		$checker = $this->getPermissionChecker();

		$this->expectException( InvalidArgumentException::class );

		$checker->getPermissionStatusForEntityId(
			$this->getTestUser()->getUser(),
			'turn-into-an-elephant',
			$this->getExistingItem()->getId()
		);
	}

	private function getNamespaceLookup() {
		// TODO: do not use those constants?
		return new EntityNamespaceLookup( [ 'item' => WB_NS_ITEM, 'property' => WB_NS_PROPERTY ] );
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getTitleLookup() {
		$lookup = $this->createMock( EntityTitleLookup::class );

		$lookup->method( 'getTitleForId' )
			->willReturnCallback( function( EntityId $id ) {
				if ( $id->getSerialization() === self::EXISTING_ITEM_ID ) {
					return Title::newFromLinkTarget( new TitleValue( WB_NS_ITEM, 'Test_Item' ) );
				}
				if ( $id->getSerialization() === self::EXISTING_PROPERTY_ID ) {
					return Title::newFromLinkTarget( new TitleValue( WB_NS_PROPERTY, 'Test_Property' ) );
				}
				return null;
			} );

		return $lookup;
	}

	private function getPermissionChecker() {
		return new WikiPageEntityStorePermissionChecker(
			$this->getNamespaceLookup(),
			$this->getTitleLookup(),
			$this->getServiceContainer()->getPermissionManager(),
			[
				'read',
				'edit',
				'createpage',
				'property-create',
				'item-term',
				'property-term',
				'item-redirect',
				'item-merge',
			]
		);
	}

	private function anyUserHasPermissions( array $permissions ) {
		// All allowed by default, have tests specify explicitly what permission they rely on.
		$defaultPermissions = [
			'read' => true,
			'edit' => true,
			'createpage' => true,
			'property-create' => true,
			'item-term' => true,
			'property-term' => true,
			'item-redirect' => true,
			'item-merge' => true,
		];

		$permissions = array_merge( $defaultPermissions, $permissions );

		$this->setMwGlobals( 'wgGroupPermissions', [ '*' => $permissions ] );
	}

	private static function getExistingItem(): Item {
		return new Item( new ItemId( self::EXISTING_ITEM_ID ) );
	}

	private static function getNonExistingItem(): Item {
		return new Item( new ItemId( self::NON_EXISTING_ITEM_ID ) );
	}

	private static function getNonExistingItemWithNullId(): Item {
		return new Item( null );
	}

	private static function getExistingProperty(): Property {
		return new Property( new NumericPropertyId( self::EXISTING_PROPERTY_ID ), null, 'test' );
	}

	private static function getNonExistingProperty(): Property {
		return new Property( new NumericPropertyId( self::NON_EXISTING_PROPERTY_ID ), null, 'test' );
	}

	private static function getNonExistingPropertyWithNullId(): Property {
		return new Property( null, null, 'test' );
	}

	/**
	 * @param string $action
	 * @param EntityDocument $entity
	 */
	private function assertUserIsAllowedTo( $action, EntityDocument $entity ) {
		$user = $this->getTestUser()->getUser();

		$permissionChecker = $this->getPermissionChecker();
		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			$action,
			$entity
		);

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId(
				$user,
				$action,
				$entity->getId()
			);
			$this->assertTrue( $statusForId->isOK() );
		}

		$this->assertTrue( $statusForEntity->isOK() );
	}

	/**
	 * @param string $action
	 * @param EntityDocument $entity
	 */
	private function assertItIsForbiddenForUserTo( $action, EntityDocument $entity ) {
		$user = $this->getTestUser()->getUser();

		$permissionChecker = $this->getPermissionChecker();
		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			$action,
			$entity
		);

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId(
				$user,
				$action,
				$entity->getId()
			);
			$this->assertFalse( $statusForId->isOK() );
		}

		$this->assertFalse( $statusForEntity->isOK() );
	}

}
