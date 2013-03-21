<?php

namespace Wikibase\Test;
use Diff\Diff;
use Diff\MapDiffer;

/**
 * Tests for the Wikibase\DiffChange class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
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
		$change = \Wikibase\DiffChange::newFromDiff( $diff );

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
