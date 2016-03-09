<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\DataModel\SiteLink;

/**
 * @covers Wikibase\DataModel\Services\Diff\ItemDiff
 *
 * @SuppressWarnings(PHPMD)
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jens Ohlig <jens.ohlig@wikimedia.de>
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Michał Łazowik
 */
class ItemDiffTest extends EntityDiffOldTest {

	public function provideApplyData() {
		$originalTests = $this->generateApplyData( Item::ENTITY_TYPE );
		$tests = array();

		/**
		 * @var Item $a
		 * @var Item $b
		 */

		// add link ------------------------------
		$a = new Item();
		$a->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			array(
				new ItemId( 'Q42' ),
				new ItemId( 'Q3' )
			)
		);

		$b = $a->copy();
		$b->getSiteLinkList()->addNewSiteLink(
			'dewiki',
			'Test',
			array(
				new ItemId( 'Q42' )
			)
		);

		$tests[] = array( $a, $b );

		// add badges
		$a = new Item();
		$a->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			array(
				new ItemId( 'Q42' ),
			)
		);

		$b = new Item();
		$b->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			array(
				new ItemId( 'Q42' ),
				new ItemId( 'Q3' )
			)
		);

		$tests[] = array( $a, $b );

		// remove badges
		$a = new Item();
		$a->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			array(
				new ItemId( 'Q42' ),
				new ItemId( 'Q3' )
			)
		);

		$b = new Item();
		$b->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			array(
				new ItemId( 'Q42' )
			)
		);

		// modify badges
		$a = new Item();
		$a->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			array(
				new ItemId( 'Q41' ),
				new ItemId( 'Q3' )
			)
		);

		$b = new Item();
		$b->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			array(
				new ItemId( 'Q42' ),
				new ItemId( 'Q3' )
			)
		);

		$tests[] = array( $a, $b );

		// remove link
		$a = new Item();
		$a->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			array(
				new ItemId( 'Q42' )
			)
		);
		$a->getSiteLinkList()->addNewSiteLink(
			'dewiki',
			'Test',
			array(
				new ItemId( 'Q3' )
			)
		);

		$b = $a->copy();
		$b->getSiteLinkList()->removeLinkWithSiteId( 'enwiki' );

		$tests[] = array( $a, $b );

		// change link
		$a = new Item();
		$a->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			array(
				new ItemId( 'Q42' ),
				new ItemId( 'Q3' )
			)
		);

		$b = new Item();
		$b->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test!!!',
			array(
				new ItemId( 'Q42' ),
				new ItemId( 'Q3' )
			)
		);

		$tests[] = array( $a, $b );

		return array_merge( $originalTests, $tests );
	}

	/**
	 * @dataProvider provideApplyData
	 */
	public function testApply( Item $a, Item $b ) {
		parent::testApply( $a, $b );

		/**
		 * @var Item $a
		 * @var Item $b
		 * @var SiteLink[] $siteLinks
		 */
		$siteLinks = array_merge(
			$a->getSiteLinkList()->toArray(),
			$b->getSiteLinkList()->toArray()
		);

		foreach ( $siteLinks as $siteLink ) {
			$aLink = $a->getSiteLinkList()->getBySiteId( $siteLink->getSiteId() );
			$bLink = $a->getSiteLinkList()->getBySiteId( $siteLink->getSiteId() );

			$this->assertEquals( $aLink->getPageName(), $bLink->getPageName() );

			$aBadges = $aLink->getBadges();
			$bBadges = $bLink->getBadges();
			$this->assertEquals( sort( $aBadges ), sort( $bBadges ) );
		}
	}

	public function isEmptyProvider() {
		$argLists = array();

		$argLists['no ops'] = array( array(), true );

		$argLists['label changed'] = array(
			array( 'label' => new Diff( array( 'x' => new DiffOpAdd( 'foo' ) ) ) ),
			false
		);

		$argLists['empty links diff'] = array(
			array( 'links' => new Diff( array(), true ) ),
			true
		);

		$argLists['non-empty links diff'] = array(
			array( 'links' => new Diff( array( new DiffOpAdd( 'foo' ) ), true ) ),
			false
		);

		return $argLists;
	}

	/**
	 * @dataProvider isEmptyProvider
	 * @param Diff[] $diffOps
	 * @param bool $isEmpty
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
		$oldErrorLevel = error_reporting( E_USER_ERROR );

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

		$this->assertInstanceOf( 'Diff\DiffOp\Diff\Diff', $diff->getAliasesDiff() );
		$this->assertInstanceOf( 'Diff\DiffOp\Diff\Diff', $diff->getLabelsDiff() );
		$this->assertInstanceOf( 'Diff\DiffOp\Diff\Diff', $diff->getDescriptionsDiff() );
		$this->assertInstanceOf( 'Diff\DiffOp\Diff\Diff', $diff->getClaimsDiff() );
		$this->assertInstanceOf( 'Diff\DiffOp\Diff\Diff', $diff->getSiteLinkDiff() );

		error_reporting( $oldErrorLevel );
	}

}
