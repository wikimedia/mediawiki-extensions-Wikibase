<?php

namespace Wikibase\Test;

use Wikibase\ChangeRow;

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
 * @author Marius Hoch
 */
class ChangeRowTest extends \MediaWikiTestCase {

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

	public function changeProvider() {
		return array(
			array(
				new ChangeRow(
					null,
					array(
						'user_id' => 1,
						'time' => '20130101000000'
					)
				)
			)
		);
	}

	/**
	 * @dataProvider changeProvider
	 */
	public function testGetUser( ChangeRow $changeRow ) {
		$this->assertInstanceOf( '\User', $changeRow->getUser() );
	}

	/**
	 * @dataProvider changeProvider
	 */
	public function testGetAge( ChangeRow $changeRow ) {
		// Don't assert on equalness because all previous code takes time!
		$this->assertTrue(
			// the time used is one above the minimum run time (4s) for the test,
			// still the normal difference to observe would be 1s.
			abs( ( time() - (int)wfTimestamp( TS_UNIX, '20130101000000' ) ) - $changeRow->getAge() ) <= 5
		);
	}

	/**
	 * @dataProvider changeProvider
	 */
	public function testGetTime( ChangeRow $changeRow ) {
		$this->assertEquals(
			'20130101000000',
			$changeRow->getTime()
		);
	}

	public function testGetObjectId() {
		$data = array( 'object_id' => 'p100' );
		$change = new ChangeRow( null, $data );

		$this->assertEquals(
			'p100',
			$change->getObjectId()
		);
	}

}
