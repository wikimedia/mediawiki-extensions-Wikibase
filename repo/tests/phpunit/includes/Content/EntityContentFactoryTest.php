<?php

namespace Wikibase\Repo\Tests\Content;

use InvalidArgumentException;
use MediaWiki\Interwiki\InterwikiLookup;
use MediaWiki\MediaWikiServices;
use OutOfBoundsException;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Content\EntityContentFactory
 *
 * @group Wikibase
 * @group WikibaseEntity
 * @group WikibaseContent
 *
 * @group Database
 *        ^--- just because we use the Title class
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class EntityContentFactoryTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider contentModelsProvider
	 */
	public function testGetEntityContentModels( array $contentModelIds, array $callbacks ) {
		$factory = new EntityContentFactory(
			$contentModelIds,
			$callbacks
		);

		$this->assertEquals(
			array_values( $contentModelIds ),
			array_values( $factory->getEntityContentModels() )
		);
	}

	public function contentModelsProvider() {
		$argLists = [];

		$argLists[] = [ [], [] ];
		$argLists[] = [ [ 'Foo' => 'Bar' ], [] ];
		$argLists[] = [ WikibaseRepo::getDefaultInstance()->getContentModelMappings(), [] ];

		return $argLists;
	}

	public function provideInvalidConstructorArguments() {
		return [
			[ [ null ], [] ],
			[ [], [ null ] ],
			[ [ 1 ], [] ],
			[ [], [ 'foo' ] ]
		];
	}

	/**
	 * @dataProvider provideInvalidConstructorArguments
	 */
	public function testInvalidConstructorArguments( array $contentModelIds, array $callbacks ) {
		$this->setExpectedException( InvalidArgumentException::class );

		new EntityContentFactory( $contentModelIds, $callbacks );
	}

	public function testIsEntityContentModel() {
		$factory = $this->newFactory();

		foreach ( $factory->getEntityContentModels() as $model ) {
			$this->assertTrue( $factory->isEntityContentModel( $model ) );
		}

		$this->assertFalse( $factory->isEntityContentModel( 'this-does-not-exist' ) );
	}

	protected function newFactory() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new EntityContentFactory(
			[
				'item' => CONTENT_MODEL_WIKIBASE_ITEM,
				'property' => CONTENT_MODEL_WIKIBASE_PROPERTY
			],
			[
				'item' => function() use ( $wikibaseRepo ) {
					return $wikibaseRepo->newItemHandler();
				},
				'property' => function() use ( $wikibaseRepo ) {
					return $wikibaseRepo->newPropertyHandler();
				}
			],
			MediaWikiServices::getInstance()->getInterwikiLookup()
		);
	}

	public function testGetTitleForId() {
		$factory = $this->newFactory();

		$id = new PropertyId( 'P42' );
		$title = $factory->getTitleForId( $id );

		$this->assertEquals( 'P42', $title->getText() );

		$expectedNs = $factory->getNamespaceForType( $id->getEntityType() );
		$this->assertEquals( $expectedNs, $title->getNamespace() );
	}

	public function testGetTitleForId_foreign() {
		$lookup = $this->getMock( InterwikiLookup::class );
		$lookup->method( 'isValidInterwiki' )
			->will( $this->returnValue( true ) );
		$this->setService( 'InterwikiLookup', $lookup );

		$factory = $this->newFactory();
		$title = $factory->getTitleForId( new ItemId( 'foo:Q42' ) );
		$this->assertSame( 'foo:Special:EntityPage/Q42', $title->getFullText() );
	}

	public function testGetEntityIdForTitle() {
		$factory = $this->newFactory();

		$title = Title::makeTitle( $factory->getNamespaceForType( Item::ENTITY_TYPE ), 'Q42' );
		$title->resetArticleID( 42 );

		$entityId = $factory->getEntityIdForTitle( $title );
		$this->assertEquals( 'Q42', $entityId->getSerialization() );
	}

	public function testGetEntityIds() {
		$factory = $this->newFactory();

		/** @var Title[] $titles */
		$titles = [
			 0 => Title::makeTitle( $factory->getNamespaceForType( Item::ENTITY_TYPE ), 'Q17' ),
			10 => Title::makeTitle( $factory->getNamespaceForType( Item::ENTITY_TYPE ), 'Q42' ),
			20 => Title::makeTitle( NS_HELP, 'Q42' ),
			30 => Title::makeTitle( $factory->getNamespaceForType( Item::ENTITY_TYPE ), 'XXX' ),
			40 => Title::makeTitle( $factory->getNamespaceForType( Item::ENTITY_TYPE ), 'Q144' ),
		];

		foreach ( $titles as $id => $title ) {
			$title->resetArticleID( $id );
		}

		$entityIds = $factory->getEntityIds( array_values( $titles ) );

		$this->assertArrayNotHasKey( 0, $entityIds );
		$this->assertArrayHasKey( 10, $entityIds );
		$this->assertArrayNotHasKey( 20, $entityIds );
		$this->assertArrayNotHasKey( 30, $entityIds );
		$this->assertArrayHasKey( 40, $entityIds );

		$this->assertEquals( 'Q42', $entityIds[10]->getSerialization() );
		$this->assertEquals( 'Q144', $entityIds[40]->getSerialization() );
	}

	public function testGetNamespaceForType() {
		$factory = $this->newFactory();
		$id = new ItemId( 'Q42' );

		$ns = $factory->getNamespaceForType( $id->getEntityType() );

		$this->assertGreaterThanOrEqual( 0, $ns, 'namespace' );
	}

	public function testGetContentHandlerForType() {
		$factory = $this->newFactory();

		foreach ( $factory->getEntityTypes() as $type ) {
			$model = $factory->getContentModelForType( $type );
			$handler = $factory->getContentHandlerForType( $type );

			$this->assertEquals( $model, $handler->getModelId() );
			$this->assertEquals( $type, $handler->getEntityType() );
		}

		$this->assertFalse( $factory->isEntityContentModel( 'this-does-not-exist' ) );

		$this->setExpectedException( OutOfBoundsException::class );
		$factory->getContentHandlerForType( 'foo' );
	}

	public function testGetEntityHandlerForContentModel() {
		$factory = $this->newFactory();

		foreach ( $factory->getEntityContentModels() as $model ) {
			$handler = $factory->getEntityHandlerForContentModel( $model );

			$this->assertEquals( $model, $handler->getModelID() );
		}

		$this->setExpectedException( OutOfBoundsException::class );
		$factory->getEntityHandlerForContentModel( 'foo' );
	}

	public function newFromEntityProvider() {
		$item = new Item();
		$property = Property::newFromType( 'string' );

		return [
			'item' => [ $item ],
			'property' => [ $property ],
		];
	}

	/**
	 * @dataProvider newFromEntityProvider
	 */
	public function testNewFromEntity( EntityDocument $entity ) {
		$factory = $this->newFactory();
		$content = $factory->newFromEntity( $entity );

		$this->assertFalse( $content->isRedirect() );
		$this->assertSame( $entity, $content->getEntity() );
	}

	public function newFromRedirectProvider() {
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );

		return [
			'item' => [ new EntityRedirect( $q1, $q2 ) ],
		];
	}

	/**
	 * @dataProvider newFromRedirectProvider
	 */
	public function testNewFromRedirect( EntityRedirect $redirect ) {
		$factory = $this->newFactory();
		$content = $factory->newFromRedirect( $redirect );

		$this->assertTrue( $content->isRedirect() );
		$this->assertSame( $redirect, $content->getEntityRedirect() );
		$this->assertNotNull( $content->getRedirectTarget() );
	}

	public function newFromRedirectProvider_unsupported() {
		$p1 = new PropertyId( 'P1' );
		$p2 = new PropertyId( 'P2' );

		return [
			'property' => [ new EntityRedirect( $p1, $p2 ) ],
		];
	}

	/**
	 * @dataProvider newFromRedirectProvider_unsupported
	 */
	public function testNewFromRedirect_unsupported( EntityRedirect $redirect ) {
		$factory = $this->newFactory();
		$content = $factory->newFromRedirect( $redirect );

		$this->assertNull( $content );
	}

}
