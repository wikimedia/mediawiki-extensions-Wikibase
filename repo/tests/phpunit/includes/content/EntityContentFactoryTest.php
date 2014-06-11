<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityContentFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\EntityContentFactory
 *
 * @group Wikibase
 * @group WikibaseEntity
 * @group WikibaseContent
 * @group WikibaseRepo
 *
 * @group Database
 *        ^--- just because we use the Title class
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class EntityContentFactoryTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider contentModelsProvider
	 */
	public function testGetEntityContentModels( array $contentModelIds ) {
		$factory = new EntityContentFactory(
			$contentModelIds
		);

		$this->assertEquals( $contentModelIds, $factory->getEntityContentModels() );
	}

	public function contentModelsProvider() {
		$argLists = array();

		$argLists[] = array( array() );
		$argLists[] = array( array( 'Foo' => 'Bar' ) );
		$argLists[] = array( WikibaseRepo::getDefaultInstance()->getContentModelMappings() );

		return $argLists;
	}

	public function testIsEntityContentModel() {
		$factory = $this->newFactory();

		foreach ( $factory->getEntityContentModels() as $model ) {
			$this->assertTrue( $factory->isEntityContentModel( $model ) );
		}

		$this->assertFalse( $factory->isEntityContentModel( 'this-does-not-exist' ) );
	}

	protected function newFactory() {
		return new EntityContentFactory(
			WikibaseRepo::getDefaultInstance()->getContentModelMappings()
		);
	}

	public function testGetTitleForId() {
		$factory = $this->newFactory();

		$title = $factory->getTitleForId( new ItemId( 'Q42' ) );

		$this->assertEquals( 'Q42', $title->getText() );
	}

	public function testGetNamespaceForType() {
		$factory = $this->newFactory();
		$id = new ItemId( 'Q42' );

		$ns = $factory->getNamespaceForType( $id->getEntityType() );

		$this->assertGreaterThanOrEqual( 0, $ns, 'namespace' );
	}

	public function testGetContentHandlerForType() {
		$factory = $this->newFactory();

		foreach ( $factory->getEntityTypes() as $type  ) {
			$model = $factory->getContentModelForType( $type );
			$handler = $factory->getContentHandlerForType( $type );

			$this->assertEquals( $model, $handler->getModelId() );
			$this->assertEquals( $type, $handler->getEntityType() );
		}

		$this->assertFalse( $factory->isEntityContentModel( 'this-does-not-exist' ) );
	}

	public function provideGetPermissionStatusForEntity() {
		return array(
			'read allowed for non-existing entity' => array(
				'read',
				array( 'read' => true ),
				null,
				array(
					'getPermissionStatusForEntity' => true,
					'getPermissionStatusForEntityType' => true,
				),
			),
			'edit and createpage allowed for new entity' => array(
				'edit',
				array( 'read' => true, 'edit' => true, 'createpage' => true ),
				null,
				array(
					'getPermissionStatusForEntity' => true,
					'getPermissionStatusForEntityType' => true,
				),
			),
			'implicit createpage not allowed for new entity' => array(
				'edit',
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				null,
				array(
					'getPermissionStatusForEntity' => false, // "createpage" is implicitly needed
					'getPermissionStatusForEntityType' => true, // "edit" is allowed for type
				),
			),
			'createpage not allowed' => array(
				'createpage',
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				null,
				array(
					'getPermissionStatusForEntity' => false, // "createpage" is implicitly needed
					'getPermissionStatusForEntityType' => false, // "createpage" is not allowed
				),
			),
			'edit allowed for existing item' => array(
				'edit',
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				'Q23',
				array(
					'getPermissionStatusForEntity' => true,
					'getPermissionStatusForEntityType' => true,
					'getPermissionStatusForEntityId' => true,
				),
			),
			'edit not allowed' => array(
				'edit',
				array( 'read' => true, 'edit' => false ),
				'Q23',
				array(
					'getPermissionStatusForEntity' => false,
					'getPermissionStatusForEntityType' => false,
					'getPermissionStatusForEntityId' => false,
				),
			),
			'delete not allowed' => array(
				'delete',
				array( 'read' => true, 'delete' => false ),
				null,
				array(
					'getPermissionStatusForEntity' => false,
					'getPermissionStatusForEntityType' => false,
				),
			),
		);
	}

	/**
	 * @dataProvider provideGetPermissionStatusForEntity
	 */
	public function testGetPermissionStatusForEntity( $action, $permissions, $id, $expectations ) {
		global $wgUser;

		$entity = Item::newEmpty();

		if ( $id ) {
			// "exists"
			$entity->setId( new ItemId( $id ) );
		}

		$this->stashMwGlobals( 'wgUser' );
		$this->stashMwGlobals( 'wgGroupPermissions' );

		PermissionsHelper::applyPermissions(
			// set permissions for implicit groups
			array( '*' => $permissions,
					'user' => $permissions,
					'autoconfirmed' => $permissions,
					'emailconfirmed' => $permissions ),
			array() // remove all groups not implied
		);

		$factory = $this->newFactory();

		if ( isset( $expectations['getPermissionStatusForEntity'] ) ) {
			$status = $factory->getPermissionStatusForEntity( $wgUser, $action, $entity );
			$this->assertEquals( $expectations['getPermissionStatusForEntity'], $status->isOK() );
		}

		if ( isset( $expectations['getPermissionStatusForEntityType'] ) ) {
			$status = $factory->getPermissionStatusForEntityType( $wgUser, $action, $entity->getType() );
			$this->assertEquals( $expectations['getPermissionStatusForEntityType'], $status->isOK() );
		}

		if ( isset( $expectations['getPermissionStatusForEntityId'] ) ) {
			$status = $factory->getPermissionStatusForEntityId( $wgUser, $action, $entity->getId() );
			$this->assertEquals( $expectations['getPermissionStatusForEntityId'], $status->isOK() );
		}
	}
}
