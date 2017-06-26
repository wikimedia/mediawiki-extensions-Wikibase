<?php

namespace Wikibase\Repo\Tests\Store;

use InvalidArgumentException;
use Title;
use TitleValue;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\WikiPageEntityStorePermissionChecker;

/**
 * @covers Wikibase\Repo\Store\WikiPageEntityStorePermissionChecker
 *
 * @group Database
 * @group Wikibase
 * @group medium
 *
 * @license GPL-2.0+
 */
class WikiPageEntityStorePermissionCheckerTest extends \MediaWikiTestCase {

	const EXISTING_ITEM_ID = 'Q2';
	const NON_EXISTING_ITEM_ID = 'Q3';
	const EXISTING_PROPERTY_ID = 'P2';
	const NON_EXISTING_PROPERTY_ID = 'P3';

	/**
	 * @dataProvider provideExistingEntities
	 */
	public function testEditPermissionsAreRequiredToEditExistingEntity( EntityDocument $existingEntity ) {
		$this->anyUserHasPermissions( [ 'edit' => true ] );

		$this->assertUserIsAllowedTo( EntityPermissionChecker::PERMISSION_EDIT, $existingEntity );
	}

	/**
	 * @dataProvider provideExistingEntities
	 */
	public function testEditPermissionsAreRequiredToEditExistingEntity_failures( EntityDocument $existingEntity ) {
		$this->anyUserHasPermissions( [ 'edit' => false ] );

		$this->assertItIsForbiddenForUserTo( EntityPermissionChecker::PERMISSION_EDIT, $existingEntity );
	}

	public function provideExistingEntities() {
		return [
			[ $this->getExistingItem() ],
			[ $this->getExistingProperty() ]
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

		$this->assertUserIsAllowedTo( EntityPermissionChecker::PERMISSION_EDIT, $nonExistingEntity );
	}

	public function provideNonExistingEntitiesAndPermissionsThatAllowEdit() {
		return [
			[ $this->getNonExistingItem(), [ 'createpage' => true ] ],
			[ $this->getNonExistingItemWithNullId(), [ 'createpage' => true ] ],
			[ $this->getNonExistingProperty(), [ 'createpage' => true, 'property-create' => true, ] ],
			[ $this->getNonExistingPropertyWithNullId(), [ 'createpage' => true, 'property-create' => true, ] ],
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

		$this->assertItIsForbiddenForUserTo( EntityPermissionChecker::PERMISSION_EDIT, $nonExistingentity );
	}

	public function provideNonExistingEntitiesAndPermissionsThatDisallowEdit() {
		return [
			[ $this->getNonExistingItem(), [ 'createpage' => false ] ],
			[ $this->getNonExistingItem(), [ 'edit' => false, 'createpage' => true ] ],
			[ $this->getNonExistingItemWithNullId(), [ 'createpage' => false ] ],
			[ $this->getNonExistingItemWithNullId(), [ 'edit' => false, 'createpage' => true ] ],
			[ $this->getNonExistingProperty(), [ 'createpage' => true, 'property-create' => false, ] ],
			[ $this->getNonExistingProperty(), [ 'createpage' => false, 'property-create' => true, ] ],
			[ $this->getNonExistingProperty(), [ 'createpage' => false, 'property-create' => false, ] ],
			[ $this->getNonExistingProperty(), [ 'edit' => false, 'createpage' => true, 'property-create' => true, ] ],
			[ $this->getNonExistingPropertyWithNullId(), [ 'createpage' => true, 'property-create' => false, ] ],
			[ $this->getNonExistingPropertyWithNullId(), [ 'createpage' => false, 'property-create' => true, ] ],
			[ $this->getNonExistingPropertyWithNullId(), [ 'createpage' => false, 'property-create' => false, ] ],
			[ $this->getNonExistingPropertyWithNullId(), [ 'edit' => false, 'createpage' => true, 'property-create' => true, ] ],
		];
	}

	/**
	 * @dataProvider provideNonExistingEntitiesAndPermissionsThatAllowEdit
	 */
	public function testAllRequiredPermissionsAreNeededToCreateNonExistingEntity(
		EntityDocument $nonExistentEntity,
		array $groupPermissionsAllowingCreation
	) {
		$this->anyUserHasPermissions( $groupPermissionsAllowingCreation );

		$this->assertUserIsAllowedTo( EntityPermissionChecker::PERMISSION_CREATE, $nonExistentEntity );
	}

	/**
	 * @dataProvider provideNonExistingEntitiesAndPermissionsThatDisallowEdit
	 */
	public function testAllRequiredPermissionsAreNeededToCreateNonExistingEntity_failures(
		EntityDocument $nonExistingEntity,
		array $groupPermissions
	) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertItIsForbiddenForUserTo( EntityPermissionChecker::PERMISSION_CREATE, $nonExistingEntity );
	}

	/**
	 * @dataProvider provideExistingEntitiesAndPermissionsThatAllowCreating
	 */
	public function testAllRequiredPermissionsAreNeededToCreateExistingEntity(
		EntityDocument $existingEntity,
		array $groupPermissions
	) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertUserIsAllowedTo( EntityPermissionChecker::PERMISSION_CREATE, $existingEntity );
	}

	public function provideExistingEntitiesAndPermissionsThatAllowCreating() {
		return [
			[ $this->getExistingItem(), [ 'createpage' => true ] ],
			[ $this->getExistingProperty(), [ 'createpage' => true, 'property-create' => true, ] ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndPermissionsThatDisallowCreating
	 */
	public function testAllRequiredPermissionsAreNeededToCreateExistingEntity_failures(
		EntityDocument $existingEntity,
		array $groupPermissions
	) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertItIsForbiddenForUserTo( EntityPermissionChecker::PERMISSION_CREATE, $existingEntity );
	}

	public function provideExistingEntitiesAndPermissionsThatDisallowCreating() {
		return [
			[ $this->getExistingItem(), [ 'createpage' => false ] ],
			[ $this->getExistingItem(), [ 'edit' => false, 'createpage' => true, ] ],
			[ $this->getExistingProperty(), [ 'createpage' => true, 'property-create' => false, ] ],
			[ $this->getExistingProperty(), [ 'createpage' => false, 'property-create' => true, ] ],
			[ $this->getExistingProperty(), [ 'createpage' => false, 'property-create' => false, ] ],
			[ $this->getExistingProperty(), [ 'edit' => false, 'createpage' => true, 'property-create' => true, ] ],
		];
	}

	/**
	 * @dataProvider provideAllEntities
	 */
	public function testReadPermissionsAreNeededToReadAnEntity( EntityDocument $entity ) {
		$this->anyUserHasPermissions( [ 'read' => true ] );

		$this->assertUserIsAllowedTo( EntityPermissionChecker::PERMISSION_READ, $entity );
	}

	/**
	 * @dataProvider provideAllEntities
	 */
	public function testReadPermissionsAreNeededToReadAnEntity_failures( EntityDocument $entity ) {
		$this->anyUserHasPermissions( [ 'read' => false ] );

		$this->assertItIsForbiddenForUserTo( EntityPermissionChecker::PERMISSION_READ, $entity );
	}

	public function provideAllEntities() {
		return [
			[ $this->getExistingItem() ],
			[ $this->getExistingProperty() ],
			[ $this->getNonExistingItem() ],
			[ $this->getNonExistingItemWithNullId() ],
			[ $this->getNonExistingProperty() ],
			[ $this->getNonExistingPropertyWithNullId() ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndPermissionsThatAllowMerge
	 */
	public function testAllRequiredPermissionsAreNeededToMergeEntity( EntityDocument $entity, array $groupPermissions ) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertUserIsAllowedTo( EntityPermissionChecker::PERMISSION_MERGE, $entity );
	}

	public function provideExistingEntitiesAndPermissionsThatAllowMerge() {
		return [
			[ $this->getExistingItem(), [ 'edit' => true, 'item-merge' => true ] ],
			[ $this->getExistingProperty(), [ 'edit' => true ] ],
			[ $this->getExistingProperty(), [ 'edit' => true, 'item-merge' => false ] ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndPermissionsThatDisallowMerge
	 */
	public function testAllRequiredPermissionsAreNeededToMergeEntity_failures( EntityDocument $entity, array $groupPermissions ) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertItIsForbiddenForUserTo( EntityPermissionChecker::PERMISSION_MERGE, $entity );
	}

	public function provideExistingEntitiesAndPermissionsThatDisallowMerge() {
		return [
			[ $this->getExistingItem(), [ 'edit' => true, 'item-merge' => false ] ],
			[ $this->getExistingItem(), [ 'edit' => false, 'item-merge' => true ] ],
			[ $this->getExistingItem(), [ 'edit' => false, 'item-merge' => false ] ],
			[ $this->getExistingProperty(), [ 'edit' => false ] ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndPermissionsThatAllowRedirect
	 */
	public function testAllRequiredPermissionsAreNeededToRedirectEntity( EntityDocument $entity, array $groupPermissions ) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertUserIsAllowedTo( EntityPermissionChecker::PERMISSION_REDIRECT, $entity );
	}

	public function provideExistingEntitiesAndPermissionsThatAllowRedirect() {
		return [
			[ $this->getExistingItem(), [ 'edit' => true, 'item-redirect' => true ] ],
			[ $this->getExistingProperty(), [ 'edit' => true ] ],
			[ $this->getExistingProperty(), [ 'edit' => true, 'item-redirect' => false ] ],
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

		$this->assertItIsForbiddenForUserTo( EntityPermissionChecker::PERMISSION_REDIRECT, $entity );
	}

	public function provideExistingEntitiesAndPermissionsThatDisallowRedirect() {
		return [
			[ $this->getExistingItem(), [ 'edit' => true, 'item-redirect' => false ] ],
			[ $this->getExistingItem(), [ 'edit' => false, 'item-redirect' => true ] ],
			[ $this->getExistingItem(), [ 'edit' => false, 'item-redirect' => false ] ],
			[ $this->getExistingProperty(), [ 'edit' => false ] ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndPermissionsThatAllowEditingTerms
	 */
	public function testAllRequiredPermissionsAreNeededToEditTerms( EntityDocument $entity, array $groupPermissions ) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertUserIsAllowedTo( EntityPermissionChecker::PERMISSION_EDIT_TERMS, $entity );
	}

	public function provideExistingEntitiesAndPermissionsThatAllowEditingTerms() {
		return [
			[ $this->getExistingItem(), [ 'edit' => true, 'item-term' => true ] ],
			[ $this->getExistingProperty(), [ 'edit' => true, 'property-term' => true ] ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndPermissionsThatDisallowEditingTerms
	 */
	public function testAllRequiredPermissionsAreNeededToEditTerms_failures( EntityDocument $entity, array $groupPermissions ) {
		$this->anyUserHasPermissions( $groupPermissions );

		$this->assertItIsForbiddenForUserTo( EntityPermissionChecker::PERMISSION_EDIT_TERMS, $entity );
	}

	public function provideExistingEntitiesAndPermissionsThatDisallowEditingTerms() {
		return [
			[ $this->getExistingItem(), [ 'edit' => true, 'item-term' => false ] ],
			[ $this->getExistingItem(), [ 'edit' => false, 'item-term' => true ] ],
			[ $this->getExistingItem(), [ 'edit' => false, 'item-term' => false ] ],
			[ $this->getExistingProperty(), [ 'edit' => true, 'property-term' => false ] ],
			[ $this->getExistingProperty(), [ 'edit' => false, 'property-term' => true ] ],
			[ $this->getExistingProperty(), [ 'edit' => false, 'property-term' => false ] ],
		];
	}

	public function testGivenUnknownPermission_getPermissionStatusForEntityThrowsException() {
		$checker = $this->getPermissionChecker();

		$this->setExpectedException( InvalidArgumentException::class );

		$checker->getPermissionStatusForEntity(
			$this->getTestUser()->getUser(),
			'turn-into-an-elephant',
			$this->getExistingItem()
		);
	}

	public function testGivenUnknownPermission_getPermissionStatusForEntityIdThrowsException() {
		$checker = $this->getPermissionChecker();

		$this->setExpectedException( InvalidArgumentException::class );

		$checker->getPermissionStatusForEntityId(
			$this->getTestUser()->getUser(),
			'turn-into-an-elephant',
			$this->getExistingItem()->getId()
		);
	}

	public function testGivenUnknownPermission_getPermissionStatusForEntityTypeThrowsException() {
		$checker = $this->getPermissionChecker();

		$this->setExpectedException( InvalidArgumentException::class );

		$checker->getPermissionStatusForEntityType(
			$this->getTestUser()->getUser(),
			'turn-into-an-elephant',
			$this->getExistingItem()->getType()
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
		$lookup = $this->getMock( EntityTitleLookup::class );

		$lookup->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				if ( $id->getSerialization() === self::EXISTING_ITEM_ID ) {
					return Title::newFromTitleValue( new TitleValue( WB_NS_ITEM, 'Test_Item' ) );
				}
				if ( $id->getSerialization() === self::EXISTING_PROPERTY_ID ) {
					return Title::newFromTitleValue( new TitleValue( WB_NS_PROPERTY, 'Test_Property' ) );
				}
				return null;
			} ) );

		return $lookup;
	}

	private function getPermissionChecker() {
		return new WikiPageEntityStorePermissionChecker(
			$this->getNamespaceLookup(),
			$this->getTitleLookup(),
			[
				'read',
				'edit',
				'createpage',
				'property-create',
				'item-term',
				'property-term',
				'item-redirect',
				'item-merge'
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
		return new Property( new PropertyId( self::EXISTING_PROPERTY_ID ), null, 'test' );
	}

	private function getNonExistingProperty() {
		return new Property( new PropertyId( self::NON_EXISTING_PROPERTY_ID ), null, 'test' );
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
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			$action,
			$entity->getType()
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
		$this->assertTrue( $statusForType->isOK() );
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
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			$action,
			$entity->getType()
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
		$this->assertFalse( $statusForType->isOK() );
	}

}
