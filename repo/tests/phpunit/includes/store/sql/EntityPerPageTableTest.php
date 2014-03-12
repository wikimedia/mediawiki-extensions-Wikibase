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
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$table = new EntityPerPageTable();
		$table->clear();

		foreach ( $entities as $entity ) {
			if ( $entity instanceof Property ) {
				$entity->setDataTypeId( 'string' );
			}

			$content = $wikibaseRepo->getEntityContentFactory()->newFromEntity( $entity );
			$title = $content->getTitle();

			if ( !$title || !$title->exists() ) {
				$content->save( 'test', null, EDIT_NEW );
				$title = $content->getTitle();
			}

			$table->addEntityPage( $content->getEntity()->getId(), $title->getArticleID() );
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

	public function testGetEntitiesWithoutTerm( /* $termType, $language = null, $entityType = null, $limit = 50, $offset = 0 */ ) {
		$this->markTestIncomplete( "test me!" );
	}

	public function testGetItemsWithoutSitelinks( /* $siteId = null, $limit = 50, $offset = 0 */ ) {
		$this->markTestIncomplete( "test me!" );
	}

	/**
	 * @dataProvider getEntitiesProvider
	 */
	public function testGetEntities( $entities, $type, $expected ) {
		$table = $this->newEntityPerPageTable( $entities );

		$iterator = $table->getEntities( $type );
		$actual = iterator_to_array( $iterator );

		$expectedIds = array();
		foreach( $expected as $entity ) {
			$expectedIds[] = $entity->getId();
		}
		$this->assertArrayEquals( $expectedIds, $actual );
	}

	public static function getEntitiesProvider() {
		$property = Property::newEmpty();
		$item = Item::newEmpty();

		return array(
			'empty' => array(
				array(), null, array()
			),
			'some entities' => array(
				array( $property, $item ), null, array( $property, $item )
			),
			'just properties' => array(
				array( $property, $item ), Property::ENTITY_TYPE, array( $property )
			),
			'no matches' => array(
				array( $property ), Item::ENTITY_TYPE, array()
			),
		);
	}
}
