<?php

namespace Wikibase\Test;
use Diff\MapDiff;
use Wikibase\ChangesTable;
use Wikibase\Item;
use Wikibase\EntityId;

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
 * @group WikibaseChange
 * @group XXX
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

	protected static $user = null;

	public function setup() {
		parent::setup();

		if ( !self::$user ) {
			self::$user = \User::newFromId( 0 );
			self::$user->setName( '127.0.0.1' );
		}

		$this->setMwGlobals( 'wgUser', self::$user );
	}

	public function newFromArrayProvider() {
		global $wgUser;
		$id = new EntityId( Item::ENTITY_TYPE, 42 );

		if ( !self::$user ) {
			self::$user = \User::newFromId( 0 );
			self::$user->setName( '127.0.0.1' );
		}

		$this->setMwGlobals( 'wgUser', self::$user );

		// Check that we can save and retrieve diffs.
		$diff1 = new\Wikibase\ItemDiff(
			array(
				'label' => new \Diff\Diff(
					array(
						"en" => new \Diff\DiffOpChange( "OLD", "NEW" ),
					)
				)
			)
		);

		// Make sure we can save and retrieve complex diff structures,
		// even if they contain objects as values.
		$diff2 = new\Wikibase\ItemDiff(
			array(
				'claim' => new \Diff\Diff(
					array(
						new \Diff\DiffOpAdd( new \Wikibase\Claim( new \Wikibase\PropertyNoValueSnak( 77 ) ) ),
					)
				)
			)
		);

		return array(
			array(
				array(
					'type' => 'wikibase-item~update',
					'time' => '20120101000000',
					'user_id' => $wgUser->getId(),
					'revision_id' => 9001,
					'object_id' => $id->getPrefixedId(),
					'info' => array(
						'diff' => $diff1,
					)
				),
				true
			),
			array(
				array(
					'type' => 'wikibase-item~update',
					'time' => '20120101000005',
					'user_id' => $wgUser->getId(),
					'revision_id' => 9002,
					'object_id' => $id->getPrefixedId(),
					'info' => array(
						'diff' => $diff2,
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
		global $wgUser;

		$change = ChangesTable::singleton()->newRow( $data, $loadDefaults );

		$this->assertEquals( $wgUser->getId(), $change->getUser()->getId() );

		foreach ( array( 'revision_id', 'object_id', 'user_id', 'type' ) as $field ) {
			$this->assertEquals( $data[$field], $change->getField( $field ) );
		}
	}

	/**
	 * @dataProvider newFromArrayProvider
	 */
	public function testGetClassForType( array $data ) {
		// todo: test for more entity and change types
		$this->assertEquals( 'Wikibase\ItemChange', ChangesTable::getClassForType( $data['type'] ) );
	}

	/**
	 * @dataProvider newFromArrayProvider
	 */
	public function testSaveSelectCountAndDelete( array $data, $loadDefaults = false ) {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( "Skipping because Wikibase Client should not write to foreign database table." );
		}

		$changesTable = ChangesTable::singleton();

		$change = $changesTable->newRow( $data, $loadDefaults );

		$this->assertTrue( $change->save(), "failed to save new change" );
		$id = $change->getId();

		$this->assertTrue( $id !== null, 'After saving, the change\'s ID should no longer be null!' );

		$obtainedChange = $changesTable->selectRow( null, array( 'id' => $id ) );
		$this->assertTrue( $obtainedChange !== false, 'Change could not be loaded via ORMTable!' );
		$this->assertArrayEquals( $change->getFields(), $obtainedChange->getFields(), false, true );

		$this->assertEquals( 1, $changesTable->count( array( 'id' => $id ) ) );

		foreach ( array( 'revision_id', 'object_id', 'user_id', 'type' ) as $field ) {
			$this->assertEquals( $data[$field], $obtainedChange->getField( $field ) );
		}

		$this->assertTrue( $obtainedChange->remove() );
		$this->assertEquals( 0, $changesTable->count( array( 'id' => $id ) ) );
	}

}

