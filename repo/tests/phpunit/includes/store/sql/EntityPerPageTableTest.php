<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Store\SQL\EntityPerPageTable;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Store\SQL\EntityPerPageTable
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseStore
 * @group WikibaseEntityPerPage
 * @group Database
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Marius Hoch < hoo@online.de >
 */
class EntityPerPageTableTest extends \MediaWikiTestCase {

	public function __construct( $name = null, $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->tablesUsed[] = 'wb_entity_per_page';
	}

	public function testAddEntityPage() {
		$epp = $this->newEntityPerPageTable();
		$epp->clear();

		$entityId = new ItemId( 'Q5' );
		$epp->addEntityPage( $entityId, 55 );

		$this->assertEquals( 55, $epp->getPageIdForEntityId( $entityId ) );
	}

	public function testAddRedirectPage() {
		if ( !$this->isRedirectTargetColumnSupported() ) {
			$this->markTestSkipped( 'Redirects not supported' );
		}

		$epp = $this->newEntityPerPageTable();
		$epp->clear();

		$redirectId = new ItemId( 'Q5' );
		$targetId = new ItemId( 'Q10' );
		$epp->addRedirectPage( $redirectId, 55, $targetId );

		$this->assertEquals( $targetId, $epp->getRedirectForEntityId( $redirectId ) );
		$this->assertEquals( 55, $epp->getPageIdForEntityId( $redirectId ) );

		$ids = $epp->listEntities( Item::ENTITY_TYPE, 10 );
		$this->assertEmpty( $ids, 'Redirects must not show up in ID listings' );
	}

	protected function isRedirectTargetColumnSupported() {
		return WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'useRedirectTargetColumn' );
	}

	/**
	 * @param Entity[] $entities
	 *
	 * @return EntityPerPageTable
	 */
	protected function newEntityPerPageTable( array $entities = array() ) {
		$useRedirectTargetColumn = $this->isRedirectTargetColumnSupported();
		$idParser = new BasicEntityIdParser();

		$table = new EntityPerPageTable( $idParser, $useRedirectTargetColumn );
		$table->clear();

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();

		foreach ( $entities as $entity ) {
			if ( $entity instanceof Property ) {
				$entity->setDataTypeId( 'string' );
			}

			$title = null;

			if ( $entity->getId() !== null ) {
				$title = $titleLookup->getTitleForId( $entity->getId() );
			}

			if ( !$title || !$title->exists() ) {
				$rev = $store->saveEntity( $entity, "test", $GLOBALS['wgUser'], EDIT_NEW );

				$entity = $rev->getEntity();
				$title = $titleLookup->getTitleForId( $entity->getId() );
			}

			$table->addEntityPage( $entity->getId(), $title->getArticleID() );
		}

		return $table;
	}

	protected function getIdStrings( array $entities ) {
		$ids = array_map( function ( $entity ) {
			if ( $entity instanceof Entity ) {
				$entity = $entity->getId();
			}
			return $entity->getSerialization();
		}, $entities );

		return $ids;
	}

	protected function assertEqualIds( array $expected,array $actual, $msg = null ) {
		$expectedIds = $this->getIdStrings( $expected );
		$actualIds = $this->getIdStrings( $actual );

		$this->assertArrayEquals( $expectedIds, $actualIds, false );
	}

	/**
	 * @dataProvider listEntitiesProvider
	 */
	public function testListEntities( array $entities, $type, $limit, array $expected ) {
		$table = $this->newEntityPerPageTable( $entities );

		$actual = $table->listEntities( $type, $limit );

		$this->assertEqualIds( $expected, $actual );
	}

	public function listEntitiesProvider() {
		$property = Property::newFromType( 'string' );
		$item = new Item();

		return array(
			'empty' => array(
				array(), null, 100, array()
			),
			'some entities' => array(
				array( $item, $property ), null, 100, array( $property, $item )
			),
			'just properties' => array(
				array( $item, $property ), Property::ENTITY_TYPE, 100, array( $property )
			),
			'no matches' => array(
				array( $property ), Item::ENTITY_TYPE, 100, array()
			),
		);
	}

	public function testGetPageIdForEntityId() {
		$entity = new Item();

		$epp = $this->newEntityPerPageTable( array( $entity ) );
		$entityId = $entity->getId();

		$this->assertFalse( $epp->getPageIdForEntityId( new ItemId( 'Q7435389457' ) ) );

		$pageId = $epp->getPageIdForEntityId( $entityId );
		$this->assertInternalType( 'int', $pageId );
		$this->assertGreaterThan( 0, $pageId );
	}

	public function testGetRedirectForEntityId() {
		if ( !$this->isRedirectTargetColumnSupported() ) {
			$this->markTestSkipped( 'Redirects not supported' );
		}

		$entity = new Item();
		$entity2 = new Item();

		$epp = $this->newEntityPerPageTable( array( $entity, $entity2 ) );
		$redirectId = $entity->getId();
		$targetId = $entity2->getId();

		$redirectPageId = $epp->getPageIdForEntityId( $redirectId );
		$epp->addRedirectPage( $redirectId, $redirectPageId, $targetId );

		$this->assertFalse( $epp->getRedirectForEntityId( new ItemId( 'Q7435389457' ) ) );
		$this->assertNull( $epp->getRedirectForEntityId( $targetId ) );

		$targetIdFromEpp = $epp->getRedirectForEntityId( $redirectId );
		$this->assertEquals( $targetId, $targetIdFromEpp );
	}

	/**
	 * @dataProvider getItemsWithoutSitelinksProvider
	 *
	 * @param Item[] $items
	 * @param string|null $siteId
	 * @param Item[] $expected
	 */
	public function testGetItemsWithoutSitelinks( array $items, $siteId, array $expected ) {
		$epp = $this->newEntityPerPageTable( $items );
		$withoutSitelinks = $epp->getItemsWithoutSitelinks( $siteId );

		$expectedIds = array();
		foreach ( $expected as $item ) {
			$expectedIds[] = $item->getId();
		}

		$this->assertEquals( $expectedIds, $withoutSitelinks );
	}

	public function getItemsWithoutSitelinksProvider() {
		$items = array();

		$foo = new Item();
		$foo->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo_en' );
		$items[] = $foo;

		$bar = new Item();
		$bar->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Bar_en' );
		$bar->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Bar_de' );
		$items[] = $bar;

		$baz = new Item();
		$baz->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Baz_en' );
		$baz->getSiteLinkList()->addNewSiteLink( 'frwiki', 'Baz_fr' );
		$baz->getSiteLinkList()->addNewSiteLink( 'eswiki', 'Baz_es' );
		$baz->getSiteLinkList()->addNewSiteLink( 'itwiki', 'Baz_it' );
		$items[] = $baz;

		$boo = new Item();
		$boo->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Boo_en' );
		$boo->getSiteLinkList()->addNewSiteLink( 'frwiki', 'Boo_fr' );
		$boo->getSiteLinkList()->addNewSiteLink( 'nlwiki', 'Boo_nl' );
		$items[] = $boo;

		$empty = new Item();
		$items[] = $empty;

		return array(
			'no sitelinks' => array( $items, null, array( $empty ) ),
			'no enwiki links' => array( $items, 'enwiki', array( $empty ) ),
			'no dewiki links' => array( $items, 'dewiki', array( $baz, $boo, $foo, $empty ) ),
			'no nnwiki links' => array( $items, 'nnwiki', array( $baz, $boo, $bar, $foo, $empty ) )
		);
	}

}
