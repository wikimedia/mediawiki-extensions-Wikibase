<?php

namespace Wikibase\Test;

use Diff\Diff;
use Diff\DiffOpAdd;
use Diff\DiffOpChange;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Entity;
use Wikibase\EntityDiff;
use Wikibase\Item;
use Wikibase\ItemDiff;

/**
 * @covers Wikibase\ItemDiff
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
 * @ingroup WikibaseDataModel
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseDiff
 * @group WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jens Ohlig <jens.ohlig@wikimedia.de>
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ItemDiffTest extends EntityDiffOldTest {
	//TODO: make the new EntityDiffTest also run for Items.

	public static function provideApplyData() {
		$originalTests = parent::generateApplyData( \Wikibase\Item::ENTITY_TYPE );
		$tests = array();

		/**
		 * @var Item $a
		 * @var Item $b
		 */

		// add link ------------------------------
		$a = Item::newEmpty();
		$a->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Test' ) );

		$b = $a->copy();
		$b->addSimpleSiteLink( new SimpleSiteLink(  'dewiki', 'Test' ) );

		$tests[] = array( $a, $b );

		// remove link
		$a = Item::newEmpty();
		$a->addSimpleSiteLink( new SimpleSiteLink(  'enwiki', 'Test' ), 'set' );
		$a->addSimpleSiteLink( new SimpleSiteLink(  'dewiki', 'Test' ), 'set' );

		$b = $a->copy();
		$b->removeSiteLink( 'enwiki' );

		$tests[] = array( $a, $b );

		// change link
		$a = Item::newEmpty();
		$a->addSimpleSiteLink( new SimpleSiteLink(  'enwiki', 'Test' ), 'set' );

		$b = $a->copy();
		$b->addSimpleSiteLink( new SimpleSiteLink(  'enwiki', 'Test!!!' ), 'set' );

		$tests[] = array( $a, $b );

		return array_merge( $originalTests, $tests );
	}

	/**
	 * @dataProvider provideApplyData
	 */
	public function testApply( Entity $a, Entity $b ) {
		parent::testApply( $a, $b );

		$a->patch( $a->getDiff( $b ) );

		/**
		 * @var Item $a
		 * @var Item $b
		 */

		$this->assertEquals( $a->getLabels(), $b->getLabels() );
		$this->assertEquals( $a->getDescriptions(), $b->getDescriptions() );
		$this->assertEquals( $a->getAllAliases(), $b->getAllAliases() );
		$this->assertEquals( $a->getSimpleSiteLinks(), $b->getSimpleSiteLinks() );
	}

	public function isEmptyProvider() {
		$argLists = array();

		$argLists['no ops'] = array( array(), true );

		$argLists['label changed'] = array( array( 'label' => new Diff( array( 'x' => new DiffOpAdd( 'foo' ) ) ) ), false );

		$argLists['empty links diff'] = array( array( 'links' => new Diff( array(), true ) ), true );

		$argLists['non-empty links diff'] = array( array( 'links' => new Diff( array( new DiffOpAdd( 'foo' ) ), true ) ), false );

		return $argLists;
	}

	/**
	 * @dataProvider isEmptyProvider
	 *
	 * @param array $diffOps
	 * @param boolean $isEmpty
	 */
	public function testIsEmpty( array $diffOps, $isEmpty ) {
		$diff = new ItemDiff( $diffOps );
		$this->assertEquals( $isEmpty, $diff->isEmpty() );
	}

	/**
	 * Checks that ItemDiff can handle atomic diffs for substructures.
	 * This is needed for backwards compatibility with old versions of
	 * MapDiffer: As of commit ff65735a125e, MapDiffer may generate atomic
	 * diffs for substructures even in recursive mode (bug 51363).
	 */
	public function testAtomicSubstructureWorkaround() {
		$oldErrorLevel = error_reporting( E_ERROR );

		try {
			$atomicListDiff = new DiffOpChange(
				array( 'a' => 'A', 'b' => 'B' ),
				array( 'b' => 'B', 'a' => 'A' )
			);

			$diff = new ItemDiff( array(
				'aliases' => $atomicListDiff,
				'label' => $atomicListDiff,
				'description' => $atomicListDiff,
				'claim' => $atomicListDiff,
				'links' => $atomicListDiff,
			) );

			$this->assertInstanceOf( '\Diff\Diff', $diff->getAliasesDiff() );
			$this->assertInstanceOf( '\Diff\Diff', $diff->getLabelsDiff() );
			$this->assertInstanceOf( '\Diff\Diff', $diff->getDescriptionsDiff() );
			$this->assertInstanceOf( '\Diff\Diff', $diff->getClaimsDiff() );
			$this->assertInstanceOf( '\Diff\Diff', $diff->getSiteLinkDiff() );
		} catch ( \Exception $ex ) { // PHP 5.3 doesn't have `finally`
			// make sure we always restore the warning level
			error_reporting( $oldErrorLevel );
			throw $ex;
		}
	}

}
