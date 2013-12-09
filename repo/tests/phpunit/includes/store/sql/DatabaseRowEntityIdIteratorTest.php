<?php

namespace Wikibase\Test;

use Wikibase\DatabaseRowEntityIdIterator;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers Wikibase\DatabaseRowEntityIdIterator
 *
 * @since 0.5
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DatabaseRowEntityIdIteratorTest extends \MediaWikiTestCase {

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return string the table name
	 */
	protected function setUpTestTable( array $entityIds ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'wb_entity_per_page', '1', __METHOD__ );

		$i = 0;

		/* @var EntityId $id */
		foreach ( $entityIds as $id ) {
			$i++;

			$dbw->insert(
				'wb_entity_per_page',
				array(
					'epp_entity_id' => $id->getSerialization(),
					'epp_page_id' => $i,
				),
				__METHOD__
			);
		}

		return 'wb_entity_per_page';
	}

	/**
	 * @param $ids
	 *
	 * @return DatabaseRowEntityIdIterator
	 */
	protected function newDatabaseRowEntityIdIterator( $ids ) {
		$dbr = wfGetDB( DB_MASTER );
		$table = $this->setUpTestTable( $ids );

		$rows = $dbr->select(
			$table,
			array( 'epp_entity_id', ),
			'',
			__METHOD__
		);

		$iterator = new DatabaseRowEntityIdIterator( $rows, 'epp_entity_id', new BasicEntityIdParser() );
		return $iterator;
	}

	/**
	 * @dataProvider idProvider
	 */
	public function testIteration( $ids ) {
		$iterator = $this->newDatabaseRowEntityIdIterator( $ids );

		if ( empty( $ids ) ) {
			$this->assertFalse( $iterator->valid() );
		}

		foreach ( $iterator as $id ) {
			$this->assertInstanceOf( 'Wikibase\DataModel\Entity\EntityId', $id );
			$this->assertContains( $id, $ids, '', false, false );
		}
	}

	public static function idProvider() {
		$p10 = new PropertyId( 'P10' );
		$q30 = new ItemId( 'Q30' );

		return array(
			'empty' => array( array() ),
			'some entities' => array( array( $p10, $q30 ) ),
		);
	}
}
