<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\EntityPerPageTable;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\EntityPerPageTable
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

	/**
	 * @param Entity[] $entities
	 *
	 * @return EntityPerPageTable
	 */
	protected function newEntityPerPageTable( array $entities ) {
		$table = new EntityPerPageTable();
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

	public function testAddEntityPage( /* EntityContent $entityContent */ ) {
		$this->markTestIncomplete( "test me!" );
	}

	public function testDeleteEntityPage( /* EntityContent $entityContent */ ) {
		$this->markTestIncomplete( "test me!" );
	}

	public function testClear() {
		$this->markTestIncomplete( "test me!" );
	}

	public function testRebuild() {
		$this->markTestIncomplete( "test me!" );
	}

	public function testListEntitiesWithoutTerm( /* $termType, $language = null, $entityType = null, $limit = 50, $offset = 0 */ ) {
		$this->markTestIncomplete( "test me!" );
	}

	public function testGetItemsWithoutSitelinks( /* $siteId = null, $limit = 50, $offset = 0 */ ) {
		$this->markTestIncomplete( "test me!" );
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

		$this->assertArrayEquals( $expectedIds, $actualIds, $msg );
	}

	/**
	 * @dataProvider listEntitiesProvider
	 */
	public function testListEntities( array $entities, $type, $limit, array $expected ) {
		$table = $this->newEntityPerPageTable( $entities );

		$actual = $table->getNextBatchOfIds( $type, $limit );

		$this->assertEqualIds( $expected, $actual );
	}

	public static function listEntitiesProvider() {
		$property = Property::newEmpty();
		$item = Item::newEmpty();

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
	/**
	 * @dataProvider listEntitiesProvider_paging
	 */
	public function testListEntities_paging( array $entities, $type, $limit, array $expectedChunks ) {
		$table = $this->newEntityPerPageTable( $entities );

		foreach ( $expectedChunks as $expected ) {
			$actual = $table->getNextBatchOfIds( $type, $limit, $offset );

			$this->assertEqualIds( $expected, $actual );
		}
	}

	public static function listEntitiesProvider_paging() {
		$property = Property::newEmpty();
		$item = Item::newEmpty();
		$item2 = Item::newEmpty();

		return array(
			'limit' => array(
				// note: "item" sorted before "property".
				array( $item, $item2, $property ),
				null,
				2,
				array (
					array( $item, $item2 ),
					array( $property ),
					array(),
				)
			),
			'limit and filter' => array(
				array( $item, $item2, $property ),
				Item::ENTITY_TYPE,
				1,
				array(
					array( $item ),
					array( $item2 ),
					array(),
				)
			)
		);
	}
}
