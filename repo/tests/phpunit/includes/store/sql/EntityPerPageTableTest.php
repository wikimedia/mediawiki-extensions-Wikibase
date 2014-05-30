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

	public static function listEntitiesProvider() {
		$property = Property::newFromType( 'string' );
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

}
