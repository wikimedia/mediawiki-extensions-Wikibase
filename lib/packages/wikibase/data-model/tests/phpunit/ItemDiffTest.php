<?php

namespace Wikibase\Test;

use Diff\Diff;
use Diff\DiffOpAdd;
use Diff\DiffOpChange;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Entity;
use Wikibase\Item;
use Wikibase\ItemDiff;

/**
 * @covers Wikibase\ItemDiff
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
 * @author Michał Łazowik
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
		$a->addSimpleSiteLink(
			new SimpleSiteLink(
				'enwiki',
				'Test',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q3' )
				)
			)
		);

		$b = $a->copy();
		$b->addSimpleSiteLink(
			new SimpleSiteLink(
				'dewiki',
				'Test',
				array(
					new ItemId( 'Q42' )
				)
			)
		);


		$tests[] = array( $a, $b );

		// add badges
		$a = Item::newEmpty();
		$a->addSimpleSiteLink(
			new SimpleSiteLink(
				'enwiki',
				'Test',
				array(
					new ItemId( 'Q42' ),
				)
			)
		);

		$b = $a->copy();
		$b->addSimpleSiteLink(
			new SimpleSiteLink(
				'enwiki',
				'Test',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q3' )
				)
			)
		);

		$tests[] = array( $a, $b );

		// remove badges
		$a = Item::newEmpty();
		$a->addSimpleSiteLink(
			new SimpleSiteLink(
				'enwiki',
				'Test',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q3' )
				)
			)
		);

		$b = $a->copy();
		$b->addSimpleSiteLink(
			new SimpleSiteLink(
				'enwiki',
				'Test',
				array(
					new ItemId( 'Q42' )
				)
			)
		);

		// modify badges
		$a = Item::newEmpty();
		$a->addSimpleSiteLink(
			new SimpleSiteLink(
				'enwiki',
				'Test',
				array(
					new ItemId( 'Q41' ),
					new ItemId( 'Q3' )
				)
			)
		);

		$b = $a->copy();
		$b->addSimpleSiteLink(
			new SimpleSiteLink(
				'enwiki',
				'Test',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q3' )
				)
			)
		);

		$tests[] = array( $a, $b );

		// remove link
		$a = Item::newEmpty();
		$a->addSimpleSiteLink(
			new SimpleSiteLink(
				'enwiki',
				'Test',
				array(
					new ItemId( 'Q42' )
				)
			)
		);
		$a->addSimpleSiteLink(
			new SimpleSiteLink(
				'dewiki',
				'Test',
				array(
					new ItemId( 'Q3' )
				)
			)
		);

		$b = $a->copy();
		$b->removeSiteLink( 'enwiki' );

		$tests[] = array( $a, $b );

		// change link
		$a = Item::newEmpty();
		$a->addSimpleSiteLink(
			new SimpleSiteLink(
				'enwiki',
				'Test',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q3' )
				)
			)
		);

		$b = $a->copy();
		$b->addSimpleSiteLink(
			new SimpleSiteLink(
				'enwiki',
				'Test!!!',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q3' )
				)
			)
		);

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

		$siteLinks = $a->getSimpleSiteLinks() + $b->getSimpleSiteLinks();
		foreach ($siteLinks as $siteLink) {
			$aLink = $a->getSimpleSiteLink( $siteLink->getSiteId() );
			$bLink = $a->getSimpleSiteLink( $siteLink->getSiteId() );

			$this->assertEquals( $aLink->getPageName(), $bLink->getPageName() );

			$aBadges = $aLink->getBadges();
			$bBadges = $bLink->getBadges();
			$this->assertEquals( sort( $aBadges ), sort( $bBadges ) );
		}
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
