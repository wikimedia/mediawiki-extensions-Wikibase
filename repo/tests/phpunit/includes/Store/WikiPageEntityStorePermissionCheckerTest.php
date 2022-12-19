<?php

namespace Wikibase\Repo\Tests\Store;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Title;
use TitleValue;
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

	public function provideExistingEntities() {
		return [
			'existing item' => [ $this->getExistingItem() ],
			'existing property' => [ $this->getExistingProperty() ],
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

	public function provideExistingEntitiesAndPermissionsThatDisallowEdit() {
		return [
			'existing item, no edit permission' => [ $this->getExistingItem(), [ 'edit' => false ] ],
			'existing item, no read permission' => [ $this->getExistingItem(), [ 'read' => false, 'edit' => true ] ],
			'existing property, no edit permission' => [ $this->getExistingProperty(), [ 'edit' => false ] ],
			'existing property, no read permission' => [ $this->getExistingProperty(), [ 'read' => false, 'edit' => true ] ],
		];
	}

	/**
	 * @dataProvider provideNonExistingEntitiesAndPermissionsThatAllowEdit
	 */
	public function testAllRequiredPermissionsAreNeededToEditNonExistingEntity(
		EntityDocument $nonExistingEntity,
		array $groupPermissions
	) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertUserIsAllowedTo( EntityPermissionChecker::ACTION_EDIT, $nonExistingEntity );
	}

	public function provideNonExistingEntitiesAndPermissionsThatAllowEdit() {
		return [
			'non-existing item, createpage permission' => [
				$this->getNonExistingItem(),
				[ 'createpage' => true ],
			],
			'non-existing item (null ID), createpage permission' => [
				$this->getNonExistingItemWithNullId(),
				[ 'createpage' => true ],
			],
			'non-existing property, createpage and property-create permission' => [
				$this->getNonExistingProperty(),
				[ 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property (null ID), createpage and property-create permission' => [
				$this->getNonExistingPropertyWithNullId(),
				[ 'createpage' => true, 'property-create' => true ],
			],
		];
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

	public function provideNonExistingEntitiesAndPermissionsThatDisallowEdit() {
		return [
			'non-existing item, no createpage permission' => [
				$this->getNonExistingItem(),
				[ 'createpage' => false ],
			],
			'non-existing item, no edit permission' => [
				$this->getNonExistingItem(),
				[ 'edit' => false, 'createpage' => true ],
			],
			'non-existing item, no read permission' => [
				$this->getNonExistingItem(),
				[ 'read' => false, 'edit' => true, 'createpage' => true ],
			],
			'non-existing item (null ID), no createpage permission' => [
				$this->getNonExistingItemWithNullId(),
				[ 'createpage' => false ],
			],
			'non-existing item (null ID), no edit permission' => [
				$this->getNonExistingItemWithNullId(),
				[ 'edit' => false, 'createpage' => true ],
			],
			'non-existing item (null ID), no read permission' => [
				$this->getNonExistingItemWithNullId(),
				[ 'read' => false, 'edit' => true, 'createpage' => true ],
			],
			'non-existing property, no property-create permission' => [
				$this->getNonExistingProperty(),
				[ 'createpage' => true, 'property-create' => false ],
			],
			'non-existing property, no createpage permission' => [
				$this->getNonExistingProperty(),
				[ 'createpage' => false, 'property-create' => true ],
			],
			'non-existing property, no createpage nor property-create permission' => [
				$this->getNonExistingProperty(),
				[ 'createpage' => false, 'property-create' => false ],
			],
			'non-existing property, no edit permission' => [
				$this->getNonExistingProperty(),
				[ 'edit' => false, 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property, no read permission' => [
				$this->getNonExistingProperty(),
				[ 'read' => false, 'edit' => true, 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property (null ID), no property-create permission' => [
				$this->getNonExistingPropertyWithNullId(),
				[ 'createpage' => true, 'property-create' => false ],
			],
			'non-existing property (null ID), no createpage permission' => [
				$this->getNonExistingPropertyWithNullId(),
				[ 'createpage' => false, 'property-create' => true ],
			],
			'non-existing property (null ID), no createpage nor property-create permission' => [
				$this->getNonExistingPropertyWithNullId(),
				[ 'createpage' => false, 'property-create' => false ],
			],
			'non-existing property (null ID), no edit permission' => [
				$this->getNonExistingPropertyWithNullId(),
				[ 'edit' => false, 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property (null ID), no read permission' => [
				$this->getNonExistingPropertyWithNullId(),
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

	public function provideAllEntities() {
		return [
			'existing item' => [ $this->getExistingItem() ],
			'existing property' => [ $this->getExistingProperty() ],
			'non-existing item' => [ $this->getNonExistingItem() ],
			'non-existing item (null ID)' => [ $this->getNonExistingItemWithNullId() ],
			'non-existing property' => [ $this->getNonExistingProperty() ],
			'non-existing property (null ID)' => [ $this->getNonExistingPropertyWithNullId() ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndPermissionsThatAllowMerge
	 */
	public function testAllRequiredPermissionsAreNeededToMergeEntity( EntityDocument $entity, array $groupPermissions ) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertUserIsAllowedTo( EntityPermissionChecker::ACTION_MERGE, $entity );
	}

	public function provideExistingEntitiesAndPermissionsThatAllowMerge() {
		return [
			'existing item, edit and item-merge permissions' => [
				$this->getExistingItem(),
				[ 'edit' => true, 'item-merge' => true ],
			],
			// TODO: should this be even tested? Or should it return false/throw exception for properties?
			'existing property, edit permission' => [
				$this->getExistingProperty(),
				[ 'edit' => true ],
			],
			'existing property, item-merge permission is irrelevant' => [
				$this->getExistingProperty(),
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

	public function provideExistingEntitiesAndPermissionsThatDisallowMerge() {
		return [
			'existing item, no item-merge permissions' => [
				$this->getExistingItem(),
				[ 'edit' => true, 'item-merge' => false ],
			],
			'existing item, no edit permissions' => [
				$this->getExistingItem(),
				[ 'edit' => false, 'item-merge' => true ],
			],
			'existing item, no edit nor item-merge permissions' => [
				$this->getExistingItem(),
				[ 'edit' => false, 'item-merge' => false ],
			],
			'existing item, no read permissions' => [
				$this->getExistingItem(),
				[ 'read' => false, 'edit' => true, 'item-merge' => true ],
			],
			// TODO: should this be even tested? Or should it return false/throw exception for properties?
			'existing property, no edit permissions' => [
				$this->getExistingProperty(),
				[ 'edit' => false ],
			],
			'existing property, no read permissions' => [
				$this->getExistingProperty(),
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

	public function provideExistingEntitiesAndPermissionsThatAllowRedirect() {
		return [
			'existing item, edit and item-redirect permissions' => [
				$this->getExistingItem(),
				[ 'edit' => true, 'item-redirect' => true ],
			],
			// TODO: should this be even tested? Or should it return false/throw exception for properties?
			'existing property, edit permission' => [
				$this->getExistingProperty(),
				[ 'edit' => true ],
			],
			'existing property, item-redirect permission is irrelevant' => [
				$this->getExistingProperty(),
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

	public function provideExistingEntitiesAndPermissionsThatDisallowRedirect() {
		return [
			'existing item, no item-redirect permission' => [
				$this->getExistingItem(),
				[ 'edit' => true, 'item-redirect' => false ],
			],
			'existing item, no edit permission' => [
				$this->getExistingItem(),
				[ 'edit' => false, 'item-redirect' => true ],
			],
			'existing item, no edit nor item-redirect permission' => [
				$this->getExistingItem(),
				[ 'edit' => false, 'item-redirect' => false ],
			],
			'existing item, no read permission' => [
				$this->getExistingItem(),
				[ 'read' => false, 'edit' => true, 'item-redirect' => true ],
			],
			// TODO: should this be even tested? Or should it return false/throw exception for properties?
			'existing property, no edit permission' => [
				$this->getExistingProperty(),
				[ 'edit' => false ],
			],
			'existing property, no read permission' => [
				$this->getExistingProperty(),
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

	public function provideExistingEntitiesAndPermissionsThatAllowEditingTerms() {
		return [
			'existing item, edit and item-term permissions' => [
				$this->getExistingItem(),
				[ 'edit' => true, 'item-term' => true ],
			],
			'existing property, edit and property-term permissions' => [
				$this->getExistingProperty(),
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

	public function provideExistingEntitiesAndPermissionsThatDisallowEditingTerms() {
		return [
			'existing item, no item-term permission' => [
				$this->getExistingItem(),
				[ 'edit' => true, 'item-term' => false ],
			],
			'existing item, no edit permission' => [
				$this->getExistingItem(),
				[ 'edit' => false, 'item-term' => true ],
			],
			'existing item, no edit nor item-term permission' => [
				$this->getExistingItem(),
				[ 'edit' => false, 'item-term' => false ],
			],
			'existing item, no read permission' => [
				$this->getExistingItem(),
				[ 'read' => false, 'edit' => true, 'item-term' => true ],
			],
			'existing property, no property-term permission' => [
				$this->getExistingProperty(),
				[ 'edit' => true, 'property-term' => false ],
			],
			'existing property, no edit permission' => [
				$this->getExistingProperty(),
				[ 'edit' => false, 'property-term' => true ],
			],
			'existing property, no edit nor property-term permission' => [
				$this->getExistingProperty(),
				[ 'edit' => false, 'property-term' => false ],
			],
			'existing property, no read permission' => [
				$this->getExistingProperty(),
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

	public function provideNonExistingEntitiesAndPermissionsThatAllowEditingTerms() {
		return [
			'non-existing item, createpage permission' => [
				$this->getNonExistingItem(),
				[ 'createpage' => true ],
			],
			'non-existing item (null ID), createpage permission' => [
				$this->getNonExistingItemWithNullId(),
				[ 'createpage' => true ],
			],
			'non-existing property, createpage and property-create permission' => [
				$this->getNonExistingProperty(),
				[ 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property (null ID), createpage and property-create permission' => [
				$this->getNonExistingPropertyWithNullId(),
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

	public function provideNonExistingEntitiesAndPermissionsThatDisallowEditingTerms() {
		return [
			'non-existing item, no createpage permission' => [
				$this->getNonExistingItem(),
				[ 'createpage' => false ],
			],
			'non-existing item, no item-term permission' => [
				$this->getNonExistingItem(),
				[ 'item-term' => false, 'createpage' => true ],
			],
			'non-existing item, no edit permission' => [
				$this->getNonExistingItem(),
				[ 'edit' => false, 'item-term' => true, 'createpage' => true ],
			],
			'non-existing item, no read permission' => [
				$this->getNonExistingItem(),
				[ 'read' => false, 'edit' => true, 'item-term' => true, 'createpage' => true ],
			],
			'non-existing item (null ID), no createpage permission' => [
				$this->getNonExistingItemWithNullId(),
				[ 'createpage' => false ],
			],
			'non-existing item (null ID), no item-term permission' => [
				$this->getNonExistingItemWithNullId(),
				[ 'item-term' => false, 'createpage' => true ],
			],
			'non-existing item (null ID), no edit permission' => [
				$this->getNonExistingItemWithNullId(),
				[ 'edit' => false, 'item-term' => true, 'createpage' => true ],
			],
			'non-existing item (null ID), no read permission' => [
				$this->getNonExistingItemWithNullId(),
				[ 'read' => false, 'edit' => true, 'item-term' => true, 'createpage' => true ],
			],
			'non-existing property, no property-create permission' => [
				$this->getNonExistingProperty(),
				[ 'createpage' => true, 'property-create' => false ],
			],
			'non-existing property, no createpage permission' => [
				$this->getNonExistingProperty(),
				[ 'createpage' => false, 'property-create' => true ],
			],
			'non-existing property, no createpage nor property-create permission' => [
				$this->getNonExistingProperty(),
				[ 'createpage' => false, 'property-create' => false ],
			],
			'non-existing property, no property-term permission' => [
				$this->getNonExistingProperty(),
				[ 'property-term' => false, 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property, no edit permission' => [
				$this->getNonExistingProperty(),
				[ 'edit' => false, 'property-term' => true, 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property, no read permission' => [
				$this->getNonExistingProperty(),
				[ 'read' => false, 'edit' => true, 'property-term' => true, 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property (null ID), no property-create permission' => [
				$this->getNonExistingPropertyWithNullId(),
				[ 'createpage' => true, 'property-create' => false ],
			],
			'non-existing property (null ID), no createpage permission' => [
				$this->getNonExistingPropertyWithNullId(),
				[ 'createpage' => false, 'property-create' => true ],
			],
			'non-existing property (null ID), no createpage nor property-create permission' => [
				$this->getNonExistingPropertyWithNullId(),
				[ 'createpage' => false, 'property-create' => false ],
			],
			'non-existing property (null ID), no property-term permission' => [
				$this->getNonExistingPropertyWithNullId(),
				[ 'property-term' => false, 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property (null ID), no edit permission' => [
				$this->getNonExistingPropertyWithNullId(),
				[ 'edit' => false, 'property-term' => true, 'createpage' => true, 'property-create' => true ],
			],
			'non-existing property (null ID), no read permission' => [
				$this->getNonExistingPropertyWithNullId(),
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
			MediaWikiServices::getInstance()->getPermissionManager(),
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

	private function getExistingItem() {
		return new Item( new ItemId( self::EXISTING_ITEM_ID ) );
	}

	private function getNonExistingItem() {
		return new Item( new ItemId( self::NON_EXISTING_ITEM_ID ) );
	}

	private function getNonExistingItemWithNullId() {
		return new Item( null );
	}

	private function getExistingProperty() {
		return new Property( new NumericPropertyId( self::EXISTING_PROPERTY_ID ), null, 'test' );
	}

	private function getNonExistingProperty() {
		return new Property( new NumericPropertyId( self::NON_EXISTING_PROPERTY_ID ), null, 'test' );
	}

	private function getNonExistingPropertyWithNullId() {
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
