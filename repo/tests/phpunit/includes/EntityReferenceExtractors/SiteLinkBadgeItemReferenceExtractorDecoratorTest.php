<?php

namespace Wikibase\Repo\Tests\Interactors;

use ContentHandler;
use HashSiteStore;
use MediaWikiTestCase;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Status;
use TestSites;
use Title;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\ChangeOp\MergeChangeOpsFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractor;
use Wikibase\Repo\EntityReferenceExtractors\SiteLinkBadgeItemReferenceExtractorDecorator;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Interactors\ItemMergeException;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Repo\Tests\EntityModificationTestHelper;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers Wikibase\Repo\EntityReferenceExtractors\SiteLinkBadgeItemReferenceExtractorDecorator
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkBadgeItemReferenceExtractorDecoratorTest extends TestCase {

	public function testGivenEntityWithNoSiteLinks_returnsDecorateeIds() {
		$expected = [ new ItemId( 'Q123' ), new ItemId( 'Q321' ) ];
		$mockReferenceExtractor = $this->getMockReferenceExtractor( $expected );

		$instance = new SiteLinkBadgeItemReferenceExtractorDecorator( $mockReferenceExtractor );
		$this->assertEquals( $expected, $instance->extractEntityIds( new Item() ) );
	}

	// given item with sitelinks, gets badge item ids

	// given both, merges

	// uniqueness

	// given no sitelink provider - explodes

	/**
	 * @param array $returnedIds
	 * @return EntityReferenceExtractor|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getMockReferenceExtractor( array $returnedIds ) {
		$mockReferenceExtractor = $this->getMockBuilder( EntityReferenceExtractor::class )
			->getMock();
		$mockReferenceExtractor->expects( $this->any() )
			->method( 'extractEntityIds' )
			->willReturn( $returnedIds );

		return $mockReferenceExtractor;
	}

}
