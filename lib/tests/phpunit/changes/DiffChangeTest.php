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
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DiffChangeTest extends \MediaWikiTestCase {

	public function diffProvider() {
		$differ = new MapDiffer();

		return array(
			array( new Diff( array(), true ) ),
			array( new Diff( $differ->doDiff( array(), array( 'en' => 'foo' ) ), true ) ),
			array( new Diff( $differ->doDiff( array( 'en' => 'bar' ), array( 'en' => 'foo' ) ), true ) ),
			array( new Diff( $differ->doDiff( array( 'en' => 'bar' ), array( 'de' => 'bar' ) ), true ) ),
		);
	}

	/**
	 * @param Diff $diff
	 * @dataProvider diffProvider
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
