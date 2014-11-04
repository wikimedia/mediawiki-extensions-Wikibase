<?php

namespace Wikibase\Test;

use ContentHandler;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Content\EntityContentFactory
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

	private function supportsRedirects() {
		$handler = ContentHandler::getForModelID( CONTENT_MODEL_WIKIBASE_ITEM );
		return $handler->supportsRedirects();
	}

	/**
	 * @dataProvider contentModelsProvider
	 */
	public function testGetEntityContentModels( array $contentModelIds ) {
		$factory = new EntityContentFactory(
			$contentModelIds
		);

		$this->assertEquals(
			array_values( $contentModelIds ),
			array_values( $factory->getEntityContentModels() )
		);
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

	public function newFromEntityProvider() {
		$item = Item::newEmpty();
		$property = Property::newFromType( 'string' );

		return array(
			'item' => array( $item ),
			'property' => array( $property ),
		);
	}

	/**
	 * @dataProvider newFromEntityProvider
	 * @param Entity $entity
	 */
	public function testNewFromEntity( Entity $entity ) {
		$factory = $this->newFactory();
		$content = $factory->newFromEntity( $entity );

		$this->assertFalse( $content->isRedirect() );
		$this->assertSame( $entity, $content->getEntity() );
	}

	public function newFromRedirectProvider() {
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );

		return array(
			'item' => array( new EntityRedirect( $q1, $q2 ) ),
		);
	}

	/**
	 * @dataProvider newFromRedirectProvider
	 * @param EntityRedirect $redirect
	 */
	public function testNewFromRedirect( EntityRedirect $redirect ) {
		if ( !$this->supportsRedirects() ) {
			// As of 2014-07-03, redirects are still experimental.
			// So do a feature check before trying to test redirects.
			$this->markTestSkipped( 'Redirects not yet supported.' );
		}

		$factory = $this->newFactory();
		$content = $factory->newFromRedirect( $redirect );

		$this->assertTrue( $content->isRedirect() );
		$this->assertSame( $redirect, $content->getEntityRedirect() );
		$this->assertNotNull( $content->getRedirectTarget() );
	}

	public function newFromRedirectProvider_unsupported() {
		$p1 = new PropertyId( 'P1' );
		$p2 = new PropertyId( 'P2' );

		return array(
			'property' => array( new EntityRedirect( $p1, $p2 ) ),
		);
	}

	/**
	 * @dataProvider newFromRedirectProvider_unsupported
	 * @param EntityRedirect $redirect
	 */
	public function testNewFromRedirect_unsupported( EntityRedirect $redirect ) {
		$factory = $this->newFactory();
		$content = $factory->newFromRedirect( $redirect );

		$this->assertNull( $content );
	}

}
