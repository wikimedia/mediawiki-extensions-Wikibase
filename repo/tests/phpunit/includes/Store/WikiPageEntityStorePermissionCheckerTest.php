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
		// All allowed by default, have tests specify explicitly what permission they rely on.
		$defaultPermissions = [
			'read' => true,
			'edit' => true,
			'createpage' => true,
			'property-create' => true,
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

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			EntityPermissionChecker::PERMISSION_EDIT,
			$entity
		);
		$statusForId = $permissionChecker->getPermissionStatusForEntityId(
			$user,
			EntityPermissionChecker::PERMISSION_EDIT,
			$entity->getId()
		);
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			EntityPermissionChecker::PERMISSION_EDIT,
			$entity->getType()
		);

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

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			EntityPermissionChecker::PERMISSION_EDIT,
			$entity
		);
		$statusForId = $permissionChecker->getPermissionStatusForEntityId(
			$user,
			EntityPermissionChecker::PERMISSION_EDIT,
			$entity->getId()
		);
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			EntityPermissionChecker::PERMISSION_EDIT,
			$entity->getType()
		);

		$this->assertFalse( $statusForEntity->isOK() );
		$this->assertFalse( $statusForId->isOK() );
		$this->assertFalse( $statusForType->isOK() );
	}

	public function provideNonExistingEntitiesAndValidCreateGroupPermissions() {
		return [
			[ $this->getNonExistingItem(), [ 'createpage' => true ] ],
			[ $this->getNonExistingItemWithNullId(), [ 'createpage' => true ] ],
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

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			EntityPermissionChecker::PERMISSION_EDIT,
			$entity
		);
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			EntityPermissionChecker::PERMISSION_EDIT,
			$entity->getType()
		);

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId(
				$user,
				EntityPermissionChecker::PERMISSION_EDIT,
				$entity->getId()
			);
			$this->assertTrue( $statusForId->isOK() );
		}

		$this->assertTrue( $statusForEntity->isOK() );
		$this->assertTrue( $statusForType->isOK() );
	}

	public function provideNonExistingEntitiesAndInvalidCreateGroupPermissions() {
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
	 * @dataProvider provideNonExistingEntitiesAndInvalidCreateGroupPermissions
	 */
	public function testCreatePageCreateEntityPermissionsAreRequiredToEditNonExistingEntity_failures(
		EntityDocument $entity,
		array $groupPermissions
	) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			EntityPermissionChecker::PERMISSION_EDIT,
			$entity
		);
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			EntityPermissionChecker::PERMISSION_EDIT,
			$entity->getType()
		);

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId(
				$user,
				EntityPermissionChecker::PERMISSION_EDIT,
				$entity->getId()
			);
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

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			EntityPermissionChecker::PERMISSION_CREATE,
			$entity
		);
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			EntityPermissionChecker::PERMISSION_CREATE,
			$entity->getType()
		);

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId(
				$user,
				EntityPermissionChecker::PERMISSION_CREATE,
				$entity->getId()
			);
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

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			EntityPermissionChecker::PERMISSION_CREATE,
			$entity
		);
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			EntityPermissionChecker::PERMISSION_CREATE,
			$entity->getType()
		);

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId(
				$user,
				EntityPermissionChecker::PERMISSION_CREATE,
				$entity->getId()
			);
			$this->assertFalse( $statusForId->isOK() );
		}

		$this->assertFalse( $statusForEntity->isOK() );
		$this->assertFalse( $statusForType->isOK() );
	}

	public function provideExistingEntitiesAndValidCreateGroupPermissions() {
		return [
			[ $this->getExistingItem(), [ 'createpage' => true ] ],
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

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			EntityPermissionChecker::PERMISSION_CREATE,
			$entity
		);
		$statusForId = $permissionChecker->getPermissionStatusForEntityId(
			$user,
			EntityPermissionChecker::PERMISSION_CREATE,
			$entity->getId()
		);
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			EntityPermissionChecker::PERMISSION_CREATE,
			$entity->getType()
		);

		$this->assertTrue( $statusForEntity->isOK() );
		$this->assertTrue( $statusForId->isOK() );
		$this->assertTrue( $statusForType->isOK() );
	}

	public function provideExistingEntitiesAndInvalidCreateGroupPermissions() {
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
	 * @dataProvider provideExistingEntitiesAndInvalidCreateGroupPermissions
	 */
	public function testAllCreatePermissionsAreRequiredForCreatePageOnExistingEntity_failures(
		EntityDocument $entity,
		array $groupPermissions
	) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			EntityPermissionChecker::PERMISSION_CREATE,
			$entity
		);
		$statusForId = $permissionChecker->getPermissionStatusForEntityId(
			$user,
			EntityPermissionChecker::PERMISSION_CREATE,
			$entity->getId()
		);
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			EntityPermissionChecker::PERMISSION_CREATE,
			$entity->getType()
		);

		$this->assertFalse( $statusForEntity->isOK() );
		$this->assertFalse( $statusForId->isOK() );
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
	public function testReadPermissionsAreSimplyChecked( EntityDocument $entity ) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, [ 'read' => true ] );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			EntityPermissionChecker::PERMISSION_READ,
			$entity
		);
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			EntityPermissionChecker::PERMISSION_READ,
			$entity->getType()
		);

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId(
				$user,
				EntityPermissionChecker::PERMISSION_READ,
				$entity->getId()
			);
			$this->assertTrue( $statusForId->isOK() );
		}

		$this->assertTrue( $statusForEntity->isOK() );
		$this->assertTrue( $statusForType->isOK() );
	}

	/**
	 * @dataProvider provideAllEntities
	 */
	public function testReadPermissionsAreSimplyChecked_failures( EntityDocument $entity ) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, [ 'read' => false ] );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			EntityPermissionChecker::PERMISSION_READ,
			$entity
		);
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			EntityPermissionChecker::PERMISSION_READ,
			$entity->getType()
		);

		if ( $entity->getId() !== null ) {
			$statusForId = $permissionChecker->getPermissionStatusForEntityId(
				$user,
				EntityPermissionChecker::PERMISSION_READ,
				$entity->getId()
			);
			$this->assertFalse( $statusForId->isOK() );
		}

		$this->assertFalse( $statusForEntity->isOK() );
		$this->assertFalse( $statusForType->isOK() );
	}

	public function provideExistingEntitiesAndValidMergeGroupPermissions() {
		return [
			[ $this->getExistingItem(), [ 'edit' => true, 'item-merge' => true ] ],
			[ $this->getExistingProperty(), [ 'edit' => true ] ],
			[ $this->getExistingProperty(), [ 'edit' => true, 'item-merge' => false ] ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndValidMergeGroupPermissions
	 */
	public function testAllMergePermissionsAreRequiredToMergeEntity( EntityDocument $entity, array $groupPermissions ) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			EntityPermissionChecker::PERMISSION_MERGE,
			$entity
		);
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			EntityPermissionChecker::PERMISSION_MERGE,
			$entity->getType()
		);
		$statusForId = $permissionChecker->getPermissionStatusForEntityId(
			$user,
			EntityPermissionChecker::PERMISSION_MERGE,
			$entity->getId()
		);

		$this->assertTrue( $statusForEntity->isOK() );
		$this->assertTrue( $statusForType->isOK() );
		$this->assertTrue( $statusForId->isOK() );
	}

	public function provideExistingEntitiesAndInvalidMergeGroupPermissions() {
		return [
			[ $this->getExistingItem(), [ 'edit' => true, 'item-merge' => false ] ],
			[ $this->getExistingItem(), [ 'edit' => false, 'item-merge' => true ] ],
			[ $this->getExistingItem(), [ 'edit' => false, 'item-merge' => false ] ],
			[ $this->getExistingProperty(), [ 'edit' => false ] ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndInvalidMergeGroupPermissions
	 */
	public function testAllMergePermissionsAreRequiredToMergeEntity_failures( EntityDocument $entity, array $groupPermissions ) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			EntityPermissionChecker::PERMISSION_MERGE,
			$entity
		);
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			EntityPermissionChecker::PERMISSION_MERGE,
			$entity->getType()
		);
		$statusForId = $permissionChecker->getPermissionStatusForEntityId(
			$user,
			EntityPermissionChecker::PERMISSION_MERGE,
			$entity->getId()
		);

		$this->assertFalse( $statusForEntity->isOK() );
		$this->assertFalse( $statusForType->isOK() );
		$this->assertFalse( $statusForId->isOK() );
	}

	public function provideExistingEntitiesAndValidRedirectGroupPermissions() {
		return [
			[ $this->getExistingItem(), [ 'edit' => true, 'item-redirect' => true ] ],
			[ $this->getExistingProperty(), [ 'edit' => true ] ],
			[ $this->getExistingProperty(), [ 'edit' => true, 'item-redirect' => false ] ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndValidRedirectGroupPermissions
	 */
	public function testAllRedirectPermissionsAreRequiredToMergeEntity( EntityDocument $entity, array $groupPermissions ) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			EntityPermissionChecker::PERMISSION_REDIRECT,
			$entity
		);
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			EntityPermissionChecker::PERMISSION_REDIRECT,
			$entity->getType()
		);
		$statusForId = $permissionChecker->getPermissionStatusForEntityId(
			$user,
			EntityPermissionChecker::PERMISSION_REDIRECT,
			$entity->getId()
		);

		$this->assertTrue( $statusForEntity->isOK() );
		$this->assertTrue( $statusForType->isOK() );
		$this->assertTrue( $statusForId->isOK() );
	}

	public function provideExistingEntitiesAndInvalidRedirectGroupPermissions() {
		return [
			[ $this->getExistingItem(), [ 'edit' => true, 'item-redirect' => false ] ],
			[ $this->getExistingItem(), [ 'edit' => false, 'item-redirect' => true ] ],
			[ $this->getExistingItem(), [ 'edit' => false, 'item-redirect' => false ] ],
			[ $this->getExistingProperty(), [ 'edit' => false ] ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndInvalidRedirectGroupPermissions
	 */
	public function testAllRedirectPermissionsAreRequiredToMergeEntity_failures( EntityDocument $entity, array $groupPermissions ) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			EntityPermissionChecker::PERMISSION_REDIRECT,
			$entity
		);
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			EntityPermissionChecker::PERMISSION_REDIRECT,
			$entity->getType()
		);
		$statusForId = $permissionChecker->getPermissionStatusForEntityId(
			$user,
			EntityPermissionChecker::PERMISSION_REDIRECT,
			$entity->getId()
		);

		$this->assertFalse( $statusForEntity->isOK() );
		$this->assertFalse( $statusForType->isOK() );
		$this->assertFalse( $statusForId->isOK() );
	}

	public function provideExistingEntitiesAndValidEditTermGroupPermissions() {
		return [
			[ $this->getExistingItem(), [ 'edit' => true, 'item-term' => true ] ],
			[ $this->getExistingProperty(), [ 'edit' => true, 'property-term' => true ] ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndValidEditTermGroupPermissions
	 */
	public function testAllEditTermPermissionsAreRequiredToEditEntityTerms( EntityDocument $entity, array $groupPermissions ) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			EntityPermissionChecker::PERMISSION_EDIT_TERMS,
			$entity
		);
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			EntityPermissionChecker::PERMISSION_EDIT_TERMS,
			$entity->getType()
		);
		$statusForId = $permissionChecker->getPermissionStatusForEntityId(
			$user,
			EntityPermissionChecker::PERMISSION_EDIT_TERMS,
			$entity->getId()
		);

		$this->assertTrue( $statusForEntity->isOK() );
		$this->assertTrue( $statusForType->isOK() );
		$this->assertTrue( $statusForId->isOK() );
	}

	public function provideExistingEntitiesAndInvalidEditTermGroupPermissions() {
		return [
			[ $this->getExistingItem(), [ 'edit' => true, 'item-term' => false ] ],
			[ $this->getExistingItem(), [ 'edit' => false, 'item-term' => true ] ],
			[ $this->getExistingItem(), [ 'edit' => false, 'item-term' => false ] ],
			[ $this->getExistingProperty(), [ 'edit' => true, 'property-term' => false ] ],
			[ $this->getExistingProperty(), [ 'edit' => false, 'property-term' => true ] ],
			[ $this->getExistingProperty(), [ 'edit' => false, 'property-term' => false ] ],
		];
	}

	/**
	 * @dataProvider provideExistingEntitiesAndInvalidEditTermGroupPermissions
	 */
	public function testAllEditTermPermissionsAreRequiredToEditEntityTerms_failures( EntityDocument $entity, array $groupPermissions ) {
		$user = $this->getTestUser()->getUser();
		$this->applyTestPermissions( $user, $groupPermissions );

		$permissionChecker = $this->getPermissionChecker();

		$statusForEntity = $permissionChecker->getPermissionStatusForEntity(
			$user,
			EntityPermissionChecker::PERMISSION_EDIT_TERMS,
			$entity
		);
		$statusForType = $permissionChecker->getPermissionStatusForEntityType(
			$user,
			EntityPermissionChecker::PERMISSION_EDIT_TERMS,
			$entity->getType()
		);
		$statusForId = $permissionChecker->getPermissionStatusForEntityId(
			$user,
			EntityPermissionChecker::PERMISSION_EDIT_TERMS,
			$entity->getId()
		);

		$this->assertFalse( $statusForEntity->isOK() );
		$this->assertFalse( $statusForType->isOK() );
		$this->assertFalse( $statusForId->isOK() );
	}

}
