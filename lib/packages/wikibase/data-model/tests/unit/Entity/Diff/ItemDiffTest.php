<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Wikibase\DataModel\Entity\Diff\ItemDiff;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;

/**
 * @covers Wikibase\DataModel\Entity\Diff\ItemDiff
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseDiff
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jens Ohlig <jens.ohlig@wikimedia.de>
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Michał Łazowik
 */
class ItemDiffTest extends EntityDiffOldTest {

	public static function provideApplyData() {
		$originalTests = parent::generateApplyData( Item::ENTITY_TYPE );
		$tests = array();

		/**
		 * @var Item $a
		 * @var Item $b
		 */

		// add link ------------------------------
		$a = Item::newEmpty();
		$a->addSiteLink(
			new SiteLink(
				'enwiki',
				'Test',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q3' )
				)
			)
		);

		$b = $a->copy();
		$b->addSiteLink(
			new SiteLink(
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
		$a->addSiteLink(
			new SiteLink(
				'enwiki',
				'Test',
				array(
					new ItemId( 'Q42' ),
				)
			)
		);

		$b = Item::newEmpty();
		$b->addSiteLink(
			new SiteLink(
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
		$a->addSiteLink(
			new SiteLink(
				'enwiki',
				'Test',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q3' )
				)
			)
		);

		$b = Item::newEmpty();
		$b->addSiteLink(
			new SiteLink(
				'enwiki',
				'Test',
				array(
					new ItemId( 'Q42' )
				)
			)
		);

		// modify badges
		$a = Item::newEmpty();
		$a->addSiteLink(
			new SiteLink(
				'enwiki',
				'Test',
				array(
					new ItemId( 'Q41' ),
					new ItemId( 'Q3' )
				)
			)
		);

		$b = Item::newEmpty();
		$b->addSiteLink(
			new SiteLink(
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
		$a->addSiteLink(
			new SiteLink(
				'enwiki',
				'Test',
				array(
					new ItemId( 'Q42' )
				)
			)
		);
		$a->addSiteLink(
			new SiteLink(
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
		$a->addSiteLink(
			new SiteLink(
				'enwiki',
				'Test',
				array(
					new ItemId( 'Q42' ),
					new ItemId( 'Q3' )
				)
			)
		);

		$b = Item::newEmpty();
		$b->addSiteLink(
			new SiteLink(
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

		/**
		 * @var Item $a
		 * @var Item $b
		 */

		$siteLinks = array_merge(
			$a->getSiteLinks(),
			$b->getSiteLinks()
		);

		/**
		 * @var SiteLink $siteLink
		 */
		foreach ( $siteLinks as $siteLink ) {
			$aLink = $a->getSiteLink( $siteLink->getSiteId() );
			$bLink = $a->getSiteLink( $siteLink->getSiteId() );

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

		$this->assertInstanceOf( 'Diff\Diff', $diff->getAliasesDiff() );
		$this->assertInstanceOf( 'Diff\Diff', $diff->getLabelsDiff() );
		$this->assertInstanceOf( 'Diff\Diff', $diff->getDescriptionsDiff() );
		$this->assertInstanceOf( 'Diff\Diff', $diff->getClaimsDiff() );
		$this->assertInstanceOf( 'Diff\Diff', $diff->getSiteLinkDiff() );
	}

}
