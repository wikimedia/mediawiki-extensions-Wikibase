<?php

namespace Wikibase\Repo\Tests\Store;

use Title;
use TitleValue;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Store\WikiPageEntityStorePermissionChecker;

/**
 * @covers Wikibase\Lib\Store\Sql\WikiPageEntityStorePermissionChecker
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
			$this->getTitleLookup()
		);
	}

	private function applyTestPermissions( User $user, array $permissions ) {
		// TODO: Why PermissionsHelper fails when used in these tests?
		global $wgGroupPermissions;
		$this->stashMwGlobals( 'wgGroupPermissions' );

		// All allowed by default, have tests specify explicitly what permission they rely on
		// but avoid define obvious right (e.g. "read" when testing create page edit permissions)
		$defaultPermissions = [
			'read' => true,
			'edit' => true,
			'createpage' => true,
			'item-create' => true,
			'property-create' => true,
		];

		$permissions = array_merge( $defaultPermissions, $permissions );

		$oldGroups = $user->getGroups();
		foreach ( $oldGroups as $group ) {
			$user->removeGroup( $group );
		}

		$group = 'wikibase-permission-checker-unittest';

		$user->addGroup( $group );

		if ( !array_key_exists( $group, $wgGroupPermissions ) ) {
			$wgGroupPermissions[$group] = [];
		}

		$wgGroupPermissions[$group] = array_merge( $wgGroupPermissions[$group], $permissions );
		// Update implicit group permissions too
		$wgGroupPermissions['*'] = array_merge( $wgGroupPermissions['*'], $permissions );
		$wgGroupPermissions['user'] = array_merge( $wgGroupPermissions['user'], $permissions );
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

	public function provideExistingEntities() {
		return [
			[ $this->getExistingItem() ],
			[ $this->getExistingProperty() ]
		];
	}

	/**
	 * @dataProvider provideExistingEntities
	 */
	public function testEditPermissionsAreRequiredToEditExistingEntity( EntityDocument $entity ) {
		$groupPermissions = [ 'edit' => true ];

		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity( $user, 'edit', $entity );
		$statusForId = $permissionChecker->getPermissionStatusForEntityId( $user, 'edit', $entity->getId() );
		$statusForType = $permissionChecker->getPermissionStatusForEntityType( $user, 'edit', $entity->getType() );

		$this->assertTrue( $statusForEntity->isOK() );
		$this->assertTrue( $statusForId->isOK() );
		$this->assertTrue( $statusForType->isOK() );
	}

	/**
	 * @dataProvider provideExistingEntities
	 */
	public function testEditPermissionsAreRequiredToEditExistingEntity_failures( EntityDocument $entity ) {
		$groupPermissions = [ 'edit' => false ];

		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity( $user, 'edit', $entity );
		$statusForId = $permissionChecker->getPermissionStatusForEntityId( $user, 'edit', $entity->getId() );
		$statusForType = $permissionChecker->getPermissionStatusForEntityType( $user, 'edit', $entity->getType() );

		$this->assertFalse( $statusForEntity->isOK() );
		$this->assertFalse( $statusForId->isOK() );
		$this->assertFalse( $statusForType->isOK() );
	}

	public function provideNonExistingEntitiesAndValidCreateGroupPermissions() {
		return [
			[ $this->getNonExistingItem(), [ 'createpage' => true, 'item-create' => true, ] ],
			[ $this->getNonExistingItemWithNullId(), [ 'createpage' => true, 'item-create' => true, ] ],
			[ $this->getNonExistingProperty(), [ 'createpage' => true, 'property-create' => true, ] ],
			[ $this->getNonExistingPropertyWithNullId(), [ 'createpage' => true, 'property-create' => true, ] ],
		];
	}

	/**
	 * @dataProvider provideNonExistingEntitiesAndValidCreateGroupPermissions
	 */
	public function testCreatePageCreateEntityPermissionsAreRequiredToEditNonExistingEntity(
		EntityDocument $entity,
		array $groupPermissions
	) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity( $user, 'edit', $entity );
		$statusForType = $permissionChecker->getPermissionStatusForEntityType( $user, 'edit', $entity->getType() );

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId( $user, 'edit', $entity->getId() );
			$this->assertTrue( $statusForId->isOK() );
		}

		$this->assertTrue( $statusForEntity->isOK() );
		$this->assertTrue( $statusForType->isOK() );
	}

	public function provideNonExistingEntitiesAndInvalidCreateGroupPermissions() {
		return [
			[ $this->getNonExistingItem(), [ 'createpage' => true, 'item-create' => false, ] ],
			[ $this->getNonExistingItem(), [ 'createpage' => false, 'item-create' => true, ] ],
			[ $this->getNonExistingItem(), [ 'createpage' => false, 'item-create' => false, ] ],
			[ $this->getNonExistingItem(), [ 'edit' => false, 'createpage' => true, 'item-create' => true, ] ],
			[ $this->getNonExistingProperty(), [ 'createpage' => true, 'property-create' => false, ] ],
			[ $this->getNonExistingProperty(), [ 'createpage' => false, 'property-create' => true, ] ],
			[ $this->getNonExistingProperty(), [ 'createpage' => false, 'property-create' => false, ] ],
			[ $this->getNonExistingProperty(), [ 'createpage' => true, 'item-create' => true, 'property-create' => false, ] ],
			[ $this->getNonExistingProperty(), [ 'edit' => false, 'createpage' => true, 'property-create' => true, ] ],
		];
	}

	/**
	 * @dataProvider provideNonExistingEntitiesAndInvalidCreateGroupPermissions
	 */
	public function testCreatePageCreateEntityPermissionsAreRequiredToEditNonExistingEntity_failures(
		EntityDocument $entity,
		array $groupPermissions
	) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity( $user, 'edit', $entity );
		$statusForType = $permissionChecker->getPermissionStatusForEntityType( $user, 'edit', $entity->getType() );

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId( $user, 'edit', $entity->getId() );
			$this->assertFalse( $statusForId->isOK() );
		}

		$this->assertFalse( $statusForEntity->isOK() );
		$this->assertFalse( $statusForType->isOK() );
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
	 * @dataProvider provideAllEntities
	 */
	public function testReadPermissionsAreRequiredToReadEntity( EntityDocument $entity ) {
		$groupPermissions = [ 'read' => true ];

		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity( $user, 'read', $entity );
		$statusForType = $permissionChecker->getPermissionStatusForEntityType( $user, 'read', $entity->getType() );

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId( $user, 'read', $entity->getId() );
			$this->assertTrue( $statusForId->isOK() );
		}

		$this->assertTrue( $statusForEntity->isOK() );
		$this->assertTrue( $statusForType->isOK() );
	}

	/**
	 * @dataProvider provideAllEntities
	 */
	public function testReadPermissionsAreRequiredToReadEntity_failures( EntityDocument $entity ) {
		$groupPermissions = [ 'read' => false ];

		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity( $user, 'read', $entity );
		$statusForType = $permissionChecker->getPermissionStatusForEntityType( $user, 'read', $entity->getType() );

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId( $user, 'read', $entity->getId() );
			$this->assertFalse( $statusForId->isOK() );
		}

		$this->assertFalse( $statusForEntity->isOK() );
		$this->assertFalse( $statusForType->isOK() );
	}

	/**
	 * @dataProvider provideNonExistingEntitiesAndValidCreateGroupPermissions
	 */
	public function testAllCreatePermissionsAreRequiredForCreatePageOnNonexistingEntity(
		EntityDocument $entity,
		array $groupPermissions
	) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity( $user, 'createpage', $entity );
		$statusForType = $permissionChecker->getPermissionStatusForEntityType( $user, 'createpage', $entity->getType() );

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId( $user, 'createpage', $entity->getId() );
			$this->assertTrue( $statusForId->isOK() );
		}

		$this->assertTrue( $statusForEntity->isOK() );
		$this->assertTrue( $statusForType->isOK() );
	}

	/**
	 * @dataProvider provideNonExistingEntitiesAndInvalidCreateGroupPermissions
	 */
	public function testAllCreatePermissionsAreRequiredForCreatePageOnNonexistingEntity_failures(
		EntityDocument $entity,
		array $groupPermissions
	) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity( $user, 'createpage', $entity );
		$statusForType = $permissionChecker->getPermissionStatusForEntityType( $user, 'createpage', $entity->getType() );

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId( $user, 'createpage', $entity->getId() );
			$this->assertFalse( $statusForId->isOK() );
		}

		$this->assertFalse( $statusForEntity->isOK() );
		$this->assertFalse( $statusForType->isOK() );
	}

	/**
	 * @dataProvider provideNonExistingEntitiesAndValidCreateGroupPermissions
	 */
	public function testAllCreatePermissionsAreRequiredForEntityCreateOnNonexistingEntity(
		EntityDocument $entity,
		array $groupPermissions
	) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$entityType = $entity->getType();

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity( $user, $entityType . '-create', $entity );
		$statusForType = $permissionChecker->getPermissionStatusForEntityType( $user, $entityType . '-create', $entity->getType() );

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId( $user, $entityType . '-create', $entity->getId() );
			$this->assertTrue( $statusForId->isOK() );
		}

		$this->assertTrue( $statusForEntity->isOK() );
		$this->assertTrue( $statusForType->isOK() );
	}

	/**
	 * @dataProvider provideNonExistingEntitiesAndInvalidCreateGroupPermissions
	 */
	public function testAllCreatePermissionsAreRequiredForEntityCreateOnNonexistingEntity_failures(
		EntityDocument $entity,
		array $groupPermissions
	) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$entityType = $entity->getType();

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity( $user, $entityType . 'create', $entity );
		$statusForType = $permissionChecker->getPermissionStatusForEntityType( $user, $entityType . 'create', $entity->getType() );

		if ( $entity->getType() ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId( $user, $entityType . 'create', $entity->getId() );
			$this->assertFalse( $statusForId->isOK() );
		}

		$this->assertFalse( $statusForEntity->isOK() );
		$this->assertFalse( $statusForType->isOK() );
	}

	public function provideExistingEntitiesAndValidCreateGroupPermissions() {
		return [
			[ $this->getExistingItem(), [ 'createpage' => true, 'item-create' => true, ] ],
			[ $this->getExistingProperty(), [ 'createpage' => true, 'property-create' => true, ] ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndValidCreateGroupPermissions
	 */
	public function testAllCreatePermissionsAreRequiredForCreatePageOnExistingEntity(
		EntityDocument $entity,
		array $groupPermissions
	) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity( $user, 'createpage', $entity );
		$statusForId = $permissionChecker->getPermissionStatusForEntityId( $user, 'createpage', $entity->getId() );
		$statusForType = $permissionChecker->getPermissionStatusForEntityType( $user, 'createpage', $entity->getType() );

		$this->assertTrue( $statusForEntity->isOK() );
		$this->assertTrue( $statusForId->isOK() );
		$this->assertTrue( $statusForType->isOK() );
	}

	public function provideExistingEntitiesAndInvalidCreateGroupPermissions() {
		return [
			[ $this->getNonExistingItem(), [ 'createpage' => true, 'item-create' => false, ] ],
			[ $this->getNonExistingItem(), [ 'createpage' => false, 'item-create' => true, ] ],
			[ $this->getNonExistingItem(), [ 'createpage' => false, 'item-create' => false, ] ],
			[ $this->getNonExistingItem(), [ 'edit' => false, 'createpage' => true, 'item-create' => true, ] ],
			[ $this->getNonExistingProperty(), [ 'createpage' => true, 'property-create' => false, ] ],
			[ $this->getNonExistingProperty(), [ 'createpage' => false, 'property-create' => true, ] ],
			[ $this->getNonExistingProperty(), [ 'createpage' => false, 'property-create' => false, ] ],
			[ $this->getNonExistingProperty(), [ 'createpage' => true, 'item-create' => true, 'property-create' => false, ] ],
			[ $this->getNonExistingProperty(), [ 'edit' => false, 'createpage' => true, 'property-create' => true, ] ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndInvalidCreateGroupPermissions
	 */
	public function testAllCreatePermissionsAreRequiredForCreatePageOnExistingEntity_failures(
		EntityDocument $entity,
		array $groupPermissions
	) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity( $user, 'createpage', $entity );
		$statusForId = $permissionChecker->getPermissionStatusForEntityId( $user, 'createpage', $entity->getId() );
		$statusForType = $permissionChecker->getPermissionStatusForEntityType( $user, 'createpage', $entity->getType() );

		$this->assertFalse( $statusForEntity->isOK() );
		$this->assertFalse( $statusForId->isOK() );
		$this->assertFalse( $statusForType->isOK() );
	}

	/**
	 * @dataProvider provideExistingEntitiesAndValidCreateGroupPermissions
	 */
	public function testAllCreatePermissionsAreRequiredForEntityCreateOnExistingEntity(
		EntityDocument $entity,
		array $groupPermissions
	) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$entityType = $entity->getType();

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity( $user, $entityType . '-create', $entity );
		$statusForId = $permissionChecker->getPermissionStatusForEntityId( $user, $entityType . '-create', $entity->getId() );
		$statusForType = $permissionChecker->getPermissionStatusForEntityType( $user, $entityType . '-create', $entity->getType() );

		$this->assertTrue( $statusForEntity->isOK() );
		$this->assertTrue( $statusForId->isOK() );
		$this->assertTrue( $statusForType->isOK() );
	}

	/**
	 * @dataProvider provideExistingEntitiesAndInvalidCreateGroupPermissions
	 */
	public function testAllCreatePermissionsAreRequiredForEntityCreateOnExistingEntity_failures(
		EntityDocument $entity,
		array $groupPermissions
	) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$entityType = $entity->getType();

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity( $user, $entityType . '-create', $entity );
		$statusForId = $permissionChecker->getPermissionStatusForEntityId( $user, $entityType . '-create', $entity->getId() );
		$statusForType = $permissionChecker->getPermissionStatusForEntityType( $user, $entityType . '-create', $entity->getType() );

		$this->assertFalse( $statusForEntity->isOK() );
		$this->assertFalse( $statusForId->isOK() );
		$this->assertFalse( $statusForType->isOK() );
	}

	public function provideAllEntitiesAndNonEditPermissions() {
		return [
			[ $this->getExistingItem(), 'delete' ],
			[ $this->getExistingProperty(), 'delete' ],
			[ $this->getNonExistingItem(), 'delete' ],
			[ $this->getNonExistingItemWithNullId(), 'delete' ],
			[ $this->getNonExistingProperty(), 'delete' ],
			[ $this->getNonExistingPropertyWithNullId(), 'delete' ],
			[ $this->getExistingItem(), 'item-merge' ],
			[ $this->getNonExistingItem(), 'item-merge' ],
			[ $this->getNonExistingItemWithNullId(), 'item-merge' ],
			[ $this->getExistingItem(), 'item-redirect' ],
			[ $this->getNonExistingItem(), 'item-redirect' ],
			[ $this->getNonExistingItemWithNullId(), 'item-redirect' ],
			[ $this->getExistingItem(), 'item-term' ],
			[ $this->getNonExistingItem(), 'item-term' ],
			[ $this->getNonExistingItemWithNullId(), 'item-term' ],
			[ $this->getExistingProperty(), 'property-term' ],
			[ $this->getNonExistingProperty(), 'property-term' ],
			[ $this->getNonExistingPropertyWithNullId(), 'property-term' ],
		];
	}

	/**
	 * @dataProvider provideAllEntitiesAndNonEditPermissions
	 */
	public function testNonEditCreatePermissionsAreSimplyChecked( EntityDocument $entity, $permission ) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, [ $permission => true ] );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity( $user, $permission, $entity );
		$statusForType = $permissionChecker->getPermissionStatusForEntityType( $user, $permission, $entity->getType() );

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId( $user, $permission, $entity->getId() );
			$this->assertTrue( $statusForId->isOK() );
		}

		$this->assertTrue( $statusForEntity->isOK() );
		$this->assertTrue( $statusForType->isOK() );
	}

	/**
	 * @dataProvider provideAllEntitiesAndNonEditPermissions
	 */
	public function testNonEditCreatePermissionsAreSimplyChecked_failures( EntityDocument $entity, $permission ) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, [ $permission => false ] );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity( $user, $permission, $entity );
		$statusForType = $permissionChecker->getPermissionStatusForEntityType( $user, $permission, $entity->getType() );

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId( $user, $permission, $entity->getId() );
			$this->assertFalse( $statusForId->isOK() );
		}

		$this->assertFalse( $statusForEntity->isOK() );
		$this->assertFalse( $statusForType->isOK() );
	}

}
