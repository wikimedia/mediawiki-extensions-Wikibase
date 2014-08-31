<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\MapDiffer;
use Wikibase\DiffChange;

/**
 * @covers Wikibase\DiffChange
 *
 * @since 0.1
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class DiffChangeTest extends ChangeRowTest {

	public function __construct( $name = null, $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->allowedInfoKeys[] = 'diff';

		$this->allowedChangeKeys = array( // see TestChanges::getChanges()
			'property-creation',
			'property-deletion',
			'property-set-label',
			'item-creation',
			'item-deletion',
			'set-dewiki-sitelink',
			'set-enwiki-sitelink',
			'change-dewiki-sitelink',
			'change-enwiki-sitelink',
			'remove-dewiki-sitelink',
			'set-de-label',
			'set-en-label',
			'set-en-aliases',
			'item-deletion-linked',
			'remove-enwiki-sitelink',
		);
	}

	/**
	 * @see ORMRowTest::getRowClass
	 * @since 0.4
	 * @return string
	 */
	protected function getRowClass() {
		return '\Wikibase\DiffChange';
	}

	public static function provideNewFromDiff() {
		$diffs = TestChanges::getDiffs();
		$cases = array();

		foreach ( $diffs as $diff ) {
			$cases[] = array( $diff );
		}

		return $cases;
	}

	/**
	 * @param Diff $diff
	 * @dataProvider provideNewFromDiff
	 */
	public function testNewFromDiff( Diff $diff ) {
		$change = DiffChange::newFromDiff( $diff );

		$this->assertEquals( $diff->isEmpty(), $change->isEmpty() );

		$change->setDiff( new Diff() );

		$this->assertTrue( $change->isEmpty() );

		$differ = new MapDiffer();
		$diff = new Diff( $differ->doDiff( array(), array( 'en' => 'foo' ) ), true );

		$change->setDiff( $diff );

		$this->assertFalse( $change->isEmpty() );

		$this->assertEquals( $diff, $change->getDiff() );
	}

}
