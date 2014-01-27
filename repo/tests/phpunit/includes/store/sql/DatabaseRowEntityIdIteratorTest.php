<?php

namespace Wikibase\Test;

use Wikibase\DatabaseRowEntityIdIterator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\PropertyContent;
use Wikibase\ItemContent;

/**
 * @covers Wikibase\DatabaseRowEntityIdIterator
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseStore
 * @group Database
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DatabaseRowEntityIdIteratorTest extends \MediaWikiTestCase {

	public function __construct( $name = null, array $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->tablesUsed[] = 'wb_entity_per_page';
	}

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
					'epp_entity_id' => $id->getNumericId(),
					'epp_entity_type' => $id->getEntityType(),
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
			array( 'epp_entity_type', 'epp_entity_id', ),
			'',
			__METHOD__
		);

		$iterator = new DatabaseRowEntityIdIterator( $rows, 'epp_entity_type', 'epp_entity_id' );
		return $iterator;
	}

	/**
	 * @dataProvider entityProvider
	 */
	public function testIteration( $entityContents ) {
		$ids = array();

		foreach( $entityContents as $entityContent ) {
			$entityContent->save( 'foo', null, EDIT_NEW );
			$ids[] = $entityContent->getEntity()->getId();
		}

		$iterator = $this->newDatabaseRowEntityIdIterator( $ids );

		if ( empty( $ids ) ) {
			$this->assertFalse( $iterator->valid() );
		}

		foreach ( $iterator as $id ) {
			$this->assertInstanceOf( 'Wikibase\DataModel\Entity\EntityId', $id );
			$this->assertContains( $id, $ids, '', false, false );
		}
	}

	public static function entityProvider() {
		$property = PropertyContent::newEmpty();
		$property->getProperty()->setDataTypeId( 'string' );

		$item = ItemContent::newEmpty();

		return array(
			'empty' => array( array() ),
			'some entities' => array( array( $property, $item ) ),
		);
	}
}
