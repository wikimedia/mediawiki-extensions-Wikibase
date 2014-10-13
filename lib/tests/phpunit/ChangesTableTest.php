<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Wikibase\ChangesTable;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Diff\ItemDiff;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\ChangesTable
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @group Database
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
		$id = new ItemId( 'q42' );

		if ( !self::$user ) {
			self::$user = \User::newFromId( 0 );
			self::$user->setName( '127.0.0.1' );
		}

		$this->setMwGlobals( 'wgUser', self::$user );

		// Check that we can save and retrieve diffs.
		$diff1 = new ItemDiff(
			array(
				'label' => new Diff(
					array(
						"en" => new DiffOpChange( "OLD", "NEW" ),
					)
				)
			)
		);

		// Make sure we can save and retrieve complex diff structures,
		// even if they contain objects as values.
		$diff2 = new ItemDiff(
			array(
				'claim' => new Diff(
					array(
						new DiffOpAdd( new Claim( new PropertyNoValueSnak( 77 ) ) ),
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
					'object_id' => $id->getSerialization(),
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
					'object_id' => $id->getSerialization(),
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

		$this->assertWellKnownFieldsEqual( $data, $change->getFields() );
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

		$this->assertWellKnownFieldsEqual( $data, $obtainedChange->getFields() );

		$this->assertTrue( $obtainedChange->remove() );
		$this->assertEquals( 0, $changesTable->count( array( 'id' => $id ) ) );
	}

	private function assertWellKnownFieldsEqual( $expected, $actual ) {
		foreach ( array( 'revision_id', 'user_id', 'type' ) as $field ) {
			$this->assertEquals( $expected[$field], $actual[$field] );
		}

		foreach ( array( 'object_id' ) as $field ) {
			$this->assertEquals( strtolower( $expected[$field] ), strtolower( $actual[$field] ) );
		}
	}

}
