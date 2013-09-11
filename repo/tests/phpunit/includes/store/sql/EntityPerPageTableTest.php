<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EntityContent;
use Wikibase\EntityContentFactory;
use Wikibase\EntityFactory;
use Wikibase\EntityPerPageTable;
use Wikibase\Property;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\EntityPerPageTable
 *
 * @since 0.5
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseEntityPerPage
 * @group Database
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityPerPageTableTest extends \MediaWikiTestCase {

	/**
	 * @param EntityId[] $entityId
	 *
	 * @return EntityPerPageTable
	 */
	protected function newEntityPerPageTable( array $entityIds ) {
		$table = new EntityPerPageTable();
		$table->clear();

		/* @var EntityId $id */
		foreach ( $entityIds as $id ) {
			$entity = EntityFactory::singleton()->newEmpty( $id->getEntityType() );
			$entity->setId( $id );

			if ( $entity instanceof Property ) {
				$entity->setDataTypeId( 'string' );
			}

			$content = EntityContentFactory::singleton()->newFromEntity( $entity );
			$title = $content->getTitle();

			if ( !$title->exists() ) {
				$content->save();
			}

			$table->addEntityContent( $content );
		}

		return $table;
	}

	public function testAddEntityContent( /* EntityContent $entityContent */ ) {
		$this->markTestIncomplete( "test me!" );
	}

	public function testDeleteEntityContent( /* EntityContent $entityContent */ ) {
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
	public function testGetEntities( $ids ) {
		$table = $this->newEntityPerPageTable( $ids );

		$iterator = $table->getEntities();
		$actual = iterator_to_array( $iterator );

		$this->assertArrayEquals( $ids, $actual );
	}

	public static function getEntitiesProvider() {
		$p10 = new PropertyId( 'P10' );
		$q30 = new ItemId( 'Q30' );

		return array(
			'empty' => array( array() ),
			'some entities' => array( array( $p10, $q30 ) ),
		);
	}
}
