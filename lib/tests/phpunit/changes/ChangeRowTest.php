<?php

namespace Wikibase\Test;

use Wikibase\ChangeRow;
use Wikibase\ChangesTable;
use Wikibase\Settings;

/**
 * @covers Wikibase\ChangeRow
 *
 * @since 0.2
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class ChangeRowTest extends \ORMRowTest {

	/**
	 * A list of names of test changes to use. Refers to keys in the array
	 * returned by TestChanges::getChanges().
	 *
	 * Subclasses should add to this array as appropriate.
	 *
	 * @var array
	 */
	protected $allowedInfoKeys;

	/**
	 * A list of keys to allow in change's info field.
	 *
	 * Subclasses should add to this array as appropriate.
	 *
	 * @var array
	 */
	protected $allowedChangeKeys;

	/**
	 * Subclasses may want to add entries to $this->allowedInfoKeys and $this->allowedChangeKeys,
	 * as appropriate.
	 *
	 * @param string|null $name
	 * @param array  $data
	 * @param string $dataName
	 */
	public function __construct( $name = null, $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->allowedInfoKeys = array( 'metadata' );

		$this->allowedChangeKeys = array( // see TestChanges::getChanges()
			'property-creation',
			'property-deletion',
			'property-set-label',
		);
	}

	public function setUp() {
		if ( !defined( 'WB_VERSION' ) ) {
			//TODO: remove this once ChangeRow no longer needs the ChangesTable as a factory.
			$this->markTestSkipped( "Skipping because cannot test changes table on client" );
		}

		parent::setUp();
	}

	/**
	 * @since 1.20
	 * @return array
	 */
	protected function getMockValues() {
		$values = parent::getMockValues();

		// register special "data" type
		$values['data'] = array( "foo", 'bar' );
		return $values;
	}

	/**
	 * @see ORMRowTest::getRowClass
	 * @since 0.2
	 * @return string
	 */
	protected function getRowClass() {
		return '\Wikibase\ChangeRow';
	}

	/**
	 * @see ORMRowTest::getRowClass
	 * @since 0.2
	 * @return string
	 */
	protected function getTableInstance() {
		return ChangesTable::singleton();
	}

	protected function getTestChanges() {
		$changes = TestChanges::getChanges( $this->allowedChangeKeys, $this->allowedInfoKeys );
		return $changes;
	}

	public function constructorTestProvider() {
		$changes = $this->getTestChanges();
		$cases = array();

		/* @var \Wikibase\EntityChange $change */
		foreach ( $changes as $change ) {
			$cases[] = array(
				$change->toArray(),
				true
			);
		}

		return $cases;
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetUser( $changeRow ) {
		$this->assertInstanceOf( '\User', $changeRow->getUser() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetAge( $changeRow ) {
		// Don't assert on equalness because all previous code takes time!
		$this->assertTrue(
			// the time used is one above the minimum run time (4s) for the test,
			// still the normal difference to observe would be 1s.
			abs( ( time() - (int)wfTimestamp( TS_UNIX, '20130101000000' ) ) - $changeRow->getAge() ) <= 5
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetTime( $changeRow ) {
		$this->assertEquals(
			'20130101000000',
			$changeRow->getTime()
		);
	}

	public function testGetObjectId( ) {
		$data = array( 'object_id' => 'p100' );
		$change = $this->getRowInstance( $data, true );

		$this->assertEquals(
			'p100',
			$change->getObjectId()
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSaveAndLoad( ChangeRow $changeRow ) {
		$changeRow->save();
		$id = $changeRow->getId();

		/* @var ChangesTable $table */
		$table = $this->getTableInstance();
		$rows = $table->selectObjects( null, array( 'id' => $id ) );

		$this->assertEquals( 1, count( $rows ), "Expected exactly one object with the given ID" );
		$loadedRow = reset( $rows );

		$expected = $changeRow->getFields();
		$this->verifyFields( $loadedRow, $expected );
	}

}
