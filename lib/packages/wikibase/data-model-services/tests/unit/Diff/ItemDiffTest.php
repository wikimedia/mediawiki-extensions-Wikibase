<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\DataModel\SiteLink;

/**
 * @covers \Wikibase\DataModel\Services\Diff\ItemDiff
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Jens Ohlig <jens.ohlig@wikimedia.de>
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Michał Łazowik
 */
class ItemDiffTest extends EntityDiffOldTest {

	public function provideApplyData() {
		$originalTests = $this->generateApplyData( Item::ENTITY_TYPE );
		$tests = [];

		// add link ------------------------------
		$a = new Item();
		$a->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			[
				new ItemId( 'Q42' ),
				new ItemId( 'Q3' ),
			]
		);

		$b = $a->copy();
		$b->getSiteLinkList()->addNewSiteLink(
			'dewiki',
			'Test',
			[
				new ItemId( 'Q42' ),
			]
		);

		$tests[] = [ $a, $b ];

		// add badges
		$a = new Item();
		$a->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			[
				new ItemId( 'Q42' ),
			]
		);

		$b = new Item();
		$b->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			[
				new ItemId( 'Q42' ),
				new ItemId( 'Q3' ),
			]
		);

		$tests[] = [ $a, $b ];

		// remove badges
		$a = new Item();
		$a->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			[
				new ItemId( 'Q42' ),
				new ItemId( 'Q3' ),
			]
		);

		$b = new Item();
		$b->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			[
				new ItemId( 'Q42' ),
			]
		);

		// modify badges
		$a = new Item();
		$a->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			[
				new ItemId( 'Q41' ),
				new ItemId( 'Q3' ),
			]
		);

		$b = new Item();
		$b->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			[
				new ItemId( 'Q42' ),
				new ItemId( 'Q3' ),
			]
		);

		$tests[] = [ $a, $b ];

		// remove link
		$a = new Item();
		$a->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			[
				new ItemId( 'Q42' ),
			]
		);
		$a->getSiteLinkList()->addNewSiteLink(
			'dewiki',
			'Test',
			[
				new ItemId( 'Q3' ),
			]
		);

		$b = $a->copy();
		$b->getSiteLinkList()->removeLinkWithSiteId( 'enwiki' );

		$tests[] = [ $a, $b ];

		// change link
		$a = new Item();
		$a->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test',
			[
				new ItemId( 'Q42' ),
				new ItemId( 'Q3' ),
			]
		);

		$b = new Item();
		$b->getSiteLinkList()->addNewSiteLink(
			'enwiki',
			'Test!!!',
			[
				new ItemId( 'Q42' ),
				new ItemId( 'Q3' ),
			]
		);

		$tests[] = [ $a, $b ];

		return array_merge( $originalTests, $tests );
	}

	/**
	 * @dataProvider provideApplyData
	 */
	public function testApply( Item $a, Item $b ) {
		$differ = new EntityDiffer();
		$patcher = new EntityPatcher();
		$patcher->patchEntity( $a, $differ->diffEntities( $a, $b ) );

		$this->assertEquals( $b, $a );

		/** @var SiteLink[] $siteLinks */
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
		$argLists = [];

		$argLists['no ops'] = [ [], true ];

		$argLists['label changed'] = [
			[ 'label' => new Diff( [ 'x' => new DiffOpAdd( 'foo' ) ] ) ],
			false,
		];

		$argLists['empty links diff'] = [
			[ 'links' => new Diff( [], true ) ],
			true,
		];

		$argLists['non-empty links diff'] = [
			[ 'links' => new Diff( [ new DiffOpAdd( 'foo' ) ], true ) ],
			false,
		];

		return $argLists;
	}

	/**
	 * @dataProvider isEmptyProvider
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
			[ 'a' => 'A', 'b' => 'B' ],
			[ 'b' => 'B', 'a' => 'A' ]
		);

		$diff = new ItemDiff( [
			'aliases' => $atomicListDiff,
			'label' => $atomicListDiff,
			'description' => $atomicListDiff,
			'claim' => $atomicListDiff,
			'links' => $atomicListDiff,
		] );

		$this->assertInstanceOf( Diff::class, $diff->getAliasesDiff() );
		$this->assertInstanceOf( Diff::class, $diff->getLabelsDiff() );
		$this->assertInstanceOf( Diff::class, $diff->getDescriptionsDiff() );
		$this->assertInstanceOf( Diff::class, $diff->getClaimsDiff() );
		$this->assertInstanceOf( Diff::class, $diff->getSiteLinkDiff() );

		error_reporting( $oldErrorLevel );
	}

}
