<?php

namespace Wikibase\Test;
use Diff\MapDiff;
use Wikibase\ChangesTable;

/**
 * Tests for the Wikibase\ChangesTable class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 * @group Wikibase
 * @group WikibaseLib
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ChangesTableTest extends \MediaWikiTestCase {

	public function newFromArrayProvider() {
		return array(
			array(
				array(
					'type' => 'item-update',
					'user_id' => $GLOBALS['wgUser']->getId(),
					'revision_id' => 9001,
					'object_id' => 42,
					'info' => array(
						'item' => \Wikibase\ItemObject::newEmpty(),
						'diff' => \Wikibase\ItemDiff::newEmpty(),
					)
				),
				true
			),
			array(
				array(
					'type' => 'item-update',
					'user_id' => $GLOBALS['wgUser']->getId(),
					'revision_id' => 9001,
					'object_id' => 42,
					'info' => array(
						'item' => \Wikibase\ItemObject::newEmpty(),
						'diff' => \Wikibase\ItemDiff::newEmpty(),
					)
				),
				true
			),
		);
	}

	/**
	 * @dataProvider newFromArrayProvider
	 */
	public function testNewFromArray( array $data, $loadDefaults = false ) {
		$change = ChangesTable::singleton()->newRow( $data, $loadDefaults );

		$this->assertEquals( $GLOBALS['wgUser']->getId(), $change->getUser()->getId() );

		foreach ( array( 'revision_id', 'object_id', 'user_id', 'type' ) as $field ) {
			$this->assertEquals( $data[$field], $change->getField( $field ) );
		}
	}

	/**
	 * @dataProvider newFromArrayProvider
	 */
	public function testSaveSelectCountAndDelete( array $data, $loadDefaults = false ) {
		$changesTable = ChangesTable::singleton();

		$change = $changesTable->newRow( $data, $loadDefaults );

		$this->assertTrue( $change->save() );

		$id = $change->getId();

		$this->assertEquals( 1, $changesTable->count( array( 'id' => $id ) ) );

		$obtainedChange = $changesTable->selectRow( null, array( 'id' => $id ) );

		foreach ( array( 'revision_id', 'object_id', 'user_id', 'type' ) as $field ) {
			$this->assertEquals( $data[$field], $obtainedChange->getField( $field ) );
		}

		$this->assertTrue( $obtainedChange->remove() );

		$this->assertEquals( 0, $changesTable->count( array( 'id' => $id ) ) );
	}

}
	
