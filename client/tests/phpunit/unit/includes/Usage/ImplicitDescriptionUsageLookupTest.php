<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\Usage;

use ArrayIterator;
use Language;
use LinkBatch;
use MediaWiki\Cache\LinkBatchFactory;
use PHPUnit\Framework\TestCase;
use Title;
use TitleFactory;
use Wikibase\Client\Store\DescriptionLookup;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\ImplicitDescriptionUsageLookup;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers \Wikibase\Client\Usage\ImplicitDescriptionUsageLookup
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0-or-later
 */
class ImplicitDescriptionUsageLookupTest extends TestCase {

	private const TEST_CONTENT_LANGUAGE = 'qqx';
	private const TEST_PAGE_ID = 123;

	private function makeLookupForGetUsagesForPage(
		?ItemId $itemId,
		?EntityUsage $explicitUsage,
		bool $allowLocalShortDesc,
		?string $localShortDesc
	) {
		$globalSiteId = 'wiki';
		$contentLanguage = self::TEST_CONTENT_LANGUAGE;
		$pageId = self::TEST_PAGE_ID;
		$titleText = 'Title text';
		$language = $this->createMock( Language::class );
		$language->method( 'getCode' )
			->willReturn( $contentLanguage );
		$title = $this->createMock( Title::class );
		$title->method( 'getPrefixedText' )
			->willReturn( $titleText );
		$title->method( 'getPageLanguage' )
			->willReturn( $language );
		$titleFactory = $this->createMock( TitleFactory::class );
		$titleFactory->method( 'newFromID' )
			->with( $pageId )
			->willReturn( $title );
		$descriptionLookup = $this->createMock( DescriptionLookup::class );
		$descriptionLookup->method( 'getDescription' )
			->with( $title, DescriptionLookup::SOURCE_LOCAL )
			->willReturn( $localShortDesc );
		$siteLinkLookup = $this->createMock( SiteLinkLookup::class );
		$siteLinkLookup->method( 'getItemIdForLink' )
			->with( $globalSiteId, $titleText )
			->willReturn( $itemId );
		$usageLookup = $this->createMock( UsageLookup::class );
		$usageLookup->method( 'getUsagesForPage' )
			->with( $pageId )
			->willReturn(
				$explicitUsage
					? [ $explicitUsage->getIdentityString() => $explicitUsage ]
					: []
			);

		return new ImplicitDescriptionUsageLookup(
			$usageLookup,
			$titleFactory,
			$allowLocalShortDesc,
			$descriptionLookup,
			$this->createMock( LinkBatchFactory::class ),
			$globalSiteId,
			$siteLinkLookup
		);
	}

	public function testGetUsagesForPage_addsImplicitUsage() {
		$itemId = new ItemId( 'Q123' );
		$explicitUsage = new EntityUsage( $itemId, EntityUsage::SITELINK_USAGE );
		$usageLookup = $this->makeLookupForGetUsagesForPage(
			$itemId,
			$explicitUsage,
			false,
			null
		);

		$usages = $usageLookup->getUsagesForPage( self::TEST_PAGE_ID );

		$this->assertCount( 2, $usages );
		$this->assertSame( $explicitUsage, reset( $usages ) );
		$this->assertSame( $explicitUsage->getIdentityString(), key( $usages ) );
		$implicitUsage = next( $usages );
		$this->assertSame( $implicitUsage->getIdentityString(), key( $usages ) );
		$this->assertSame( $itemId, $implicitUsage->getEntityId() );
		$this->assertSame( EntityUsage::DESCRIPTION_USAGE, $implicitUsage->getAspect() );
		$this->assertSame( self::TEST_CONTENT_LANGUAGE, $implicitUsage->getModifier() );
	}

	public function testGetUsagesForPage_doesNotDuplicateExplicitUsage() {
		$itemId = new ItemId( 'Q123' );
		$explicitUsage = new EntityUsage(
			$itemId,
			EntityUsage::DESCRIPTION_USAGE,
			self::TEST_CONTENT_LANGUAGE
		);
		$usageLookup = $this->makeLookupForGetUsagesForPage(
			$itemId,
			$explicitUsage,
			false,
			null
		);

		$usages = $usageLookup->getUsagesForPage( self::TEST_PAGE_ID );

		$this->assertCount( 1, $usages );
		$this->assertEquals( $explicitUsage, reset( $usages ) );
		$this->assertSame( $explicitUsage->getIdentityString(), key( $usages ) );
	}

	public function testGetUsagesForPage_ignoresLocalDescriptionIfNotAllowed() {
		$itemId = new ItemId( 'Q123' );
		$usageLookup = $this->makeLookupForGetUsagesForPage(
			$itemId,
			null,
			false,
			'local short description'
		);

		$usages = $usageLookup->getUsagesForPage( self::TEST_PAGE_ID );

		$this->assertCount( 1, $usages );
		$implicitUsage = reset( $usages );
		$this->assertSame( $implicitUsage->getIdentityString(), key( $usages ) );
		$this->assertSame( $itemId, $implicitUsage->getEntityId() );
		$this->assertSame( EntityUsage::DESCRIPTION_USAGE, $implicitUsage->getAspect() );
		$this->assertSame( self::TEST_CONTENT_LANGUAGE, $implicitUsage->getModifier() );
	}

	public function testGetUsagesForPage_doesNotAddImplicitUsageWithLocalDescription() {
		$itemId = new ItemId( 'Q123' );
		$usageLookup = $this->makeLookupForGetUsagesForPage(
			$itemId,
			null,
			true,
			'local short description'
		);

		$usages = $usageLookup->getUsagesForPage( self::TEST_PAGE_ID );

		$this->assertSame( [], $usages );
	}

	public function testGetUsagesForPage_doesNotRemoveExplicitUsageWithLocalDescription() {
		$itemId = new ItemId( 'Q123' );
		$explicitUsage = new EntityUsage(
			$itemId,
			EntityUsage::DESCRIPTION_USAGE,
			self::TEST_CONTENT_LANGUAGE
		);
		$usageLookup = $this->makeLookupForGetUsagesForPage(
			$itemId,
			$explicitUsage,
			true,
			'local short description'
		);

		$usages = $usageLookup->getUsagesForPage( self::TEST_PAGE_ID );

		$this->assertCount( 1, $usages );
		$this->assertEquals( $explicitUsage, reset( $usages ) );
		$this->assertSame( $explicitUsage->getIdentityString(), key( $usages ) );
	}

	public function testGetUsagesForPage_doesNothingForUnlinkedPage() {
		$usageLookup = $this->makeLookupForGetUsagesForPage( null, null, false, null );

		$usages = $usageLookup->getUsagesForPage( self::TEST_PAGE_ID );

		$this->assertCount( 0, $usages );
	}

	/** @dataProvider provideRelatedAspects */
	public function testGetPagesUsing( array $aspects, bool $expect456, bool $expect789 ) {
		$globalSiteId = 'wiki';
		$entityIds = [
			// This entity ID is irrelevant to ImplicitUsageLookup,
			// but the inner lookup will return a usage for it.
			// We will assert that it’s not thrown away.
			new NumericPropertyId( 'P951' ),
			// For this entity ID, the inner lookup will already return a usage
			// equal to the implicit usage, so there will be nothing to do.
			new ItemId( 'Q123' ),
			// For this entity ID, the inner lookup will return different usage
			// from the implicit usage, so the implicit usage would need to be added.
			// However, if $expect456 is false, the implicit usage should not be added
			// because it’s not covered by the $aspects.
			new ItemId( 'Q456' ),
			// For this entity ID, the inner lookup will return no usage at all,
			// so the ImplicitUsageLookup would have to add it,
			// but only if $expect789 is true (otherwise it’s not covered by the $aspects).
			new ItemId( 'Q789' ),
			// For the title linked to this entity ID according to the SiteLinkLookup,
			// the TitleFactory will return a Title with a 0 article ID,
			// indicating that the repo thinks a page is linked to the item,
			// but on the client it does not exist (maybe it was just deleted).
			new ItemId( 'Q1000' ),
		];
		$siteLinkLookup = $this->createMock( SiteLinkLookup::class );
		$siteLinkLookup->method( 'getLinks' )
			->with( [ 123, 456, 789, 1000 ], [ $globalSiteId ] )
			->willReturn( [
				[ $globalSiteId, 'Page 123', $entityIds[1]->getNumericId() ],
				[ $globalSiteId, 'Page 456', $entityIds[2]->getNumericId() ],
				[ $globalSiteId, 'Page 789', $entityIds[3]->getNumericId() ],
				[ $globalSiteId, 'Deleted page', $entityIds[4]->getNumericId() ],
			] );
		$titleFactory = $this->mockTitleFactory();
		$linkBatchFactory = $this->mockLinkBatchFactory( [ 123, 456, 789, 0 ] );
		$usageLookup = $this->createMock( UsageLookup::class );
		$pageEntityUsages123 = new PageEntityUsages( 123, [
			new EntityUsage(
				$entityIds[1],
				EntityUsage::DESCRIPTION_USAGE,
				'lang-x-123'
			),
		] );
		$originalPageEntityUsages123 = clone $pageEntityUsages123;
		$pageEntityUsages456 = new PageEntityUsages( 456, [
			new EntityUsage( $entityIds[2], EntityUsage::STATEMENT_USAGE ),
		] );
		$originalPageEntityUsages456 = clone $pageEntityUsages456;
		$pageEntityUsages951 = new PageEntityUsages( 951, [
			new EntityUsage( $entityIds[0], EntityUsage::OTHER_USAGE ),
		] );
		$originalPageEntityUsages951 = clone $pageEntityUsages951;
		$usageLookup->method( 'getPagesUsing' )
			->with( $entityIds, $aspects )
			->willReturn( new ArrayIterator( [
				$pageEntityUsages123,
				$pageEntityUsages456,
				$pageEntityUsages951,
			] ) );

		$pageEntityUsages = ( new ImplicitDescriptionUsageLookup(
			$usageLookup,
			$titleFactory,
			false,
			$this->createMock( DescriptionLookup::class ),
			$linkBatchFactory,
			$globalSiteId,
			$siteLinkLookup
		) )->getPagesUsing( $entityIds, $aspects );

		$pageEntityUsages = iterator_to_array( $pageEntityUsages );
		$this->assertCount( $expect789 ? 4 : 3, $pageEntityUsages );

		$this->assertSame( $pageEntityUsages123, $pageEntityUsages[0] );
		$this->assertEquals( $originalPageEntityUsages123, $pageEntityUsages123 );

		$this->assertSame( $pageEntityUsages456, $pageEntityUsages[1] );
		$usages456 = array_values( $pageEntityUsages456->getUsages() );
		$originalUsages456 = array_values( $originalPageEntityUsages456->getUsages() );
		$this->assertCount( $expect456 ? 2 : 1, $usages456 );
		// we know the explicit usage comes before the implicit one,
		// because PageEntityUsages sorts them and C(laim) < D(escription)
		$this->assertSame( $originalUsages456[0], $usages456[0] );
		if ( $expect456 ) {
			$implicitUsage = $usages456[1];
			$this->assertEquals( $entityIds[2], $implicitUsage->getEntityId() );
			$this->assertSame( EntityUsage::DESCRIPTION_USAGE, $implicitUsage->getAspect() );
			$this->assertSame( 'lang-x-456', $implicitUsage->getModifier() );
		}

		$this->assertSame( $pageEntityUsages951, $pageEntityUsages[2] );
		$this->assertEquals( $originalPageEntityUsages951, $pageEntityUsages951 );

		if ( $expect789 ) {
			/** @var PageEntityUsages $pageEntityUsages789 */
			'@phan-var PageEntityUsages $pageEntityUsages789';
			$pageEntityUsages789 = $pageEntityUsages[3];
			$this->assertSame( 789, $pageEntityUsages789->getPageId() );
			$usages789 = array_values( $pageEntityUsages789->getUsages() );
			$this->assertCount( 1, $usages789 );
			$implicitUsage = $usages789[0];
			$this->assertEquals( $entityIds[3], $implicitUsage->getEntityId() );
			$this->assertSame( EntityUsage::DESCRIPTION_USAGE, $implicitUsage->getAspect() );
			$this->assertSame( 'lang-x-789', $implicitUsage->getModifier() );
		}
	}

	public function provideRelatedAspects() {
		yield 'description' => [
			'aspects' => [ EntityUsage::DESCRIPTION_USAGE ],
			'expect456' => true,
			'expect789' => true,
		];
		yield 'description in wiki content language' => [
			'aspects' => [ EntityUsage::makeAspectKey(
				EntityUsage::DESCRIPTION_USAGE,
				self::TEST_CONTENT_LANGUAGE
			) ],
			'expect456' => false,
			'expect789' => false,
		];
		yield 'description in unrelated language' => [
			'aspects' => [ EntityUsage::makeAspectKey(
				EntityUsage::DESCRIPTION_USAGE,
				'xyz'
			) ],
			'expect456' => false,
			'expect789' => false,
		];
		yield 'other and description' => [
			'aspects' => [
				EntityUsage::OTHER_USAGE,
				EntityUsage::DESCRIPTION_USAGE,
			],
			'expect456' => true,
			'expect789' => true,
		];
		yield 'unfiltered' => [
			'aspects' => [],
			'expect456' => true,
			'expect789' => true,
		];
	}

	/** @dataProvider provideUnrelatedAspects */
	public function testGetPagesUsing_doesNothingForUnrelatedAspects( array $aspects ) {
		$entityIds = [ new ItemId( 'Q123' ) ];
		$expectedPages = new ArrayIterator( [ 'opaque sentinel' ] );
		$usageLookup = $this->createMock( UsageLookup::class );
		$usageLookup->method( 'getPagesUsing' )
			->with( $entityIds, $aspects )
			->willReturn( $expectedPages );

		$actualPages = ( new ImplicitDescriptionUsageLookup(
			$usageLookup,
			$this->createMock( TitleFactory::class ),
			false,
			$this->createMock( DescriptionLookup::class ),
			$this->createMock( LinkBatchFactory::class ),
			'wiki',
			$this->createMock( SiteLinkLookup::class )
		) )->getPagesUsing( $entityIds, $aspects );

		$this->assertSame(
			iterator_to_array( $expectedPages ),
			iterator_to_array( $actualPages )
		);
	}

	public function provideUnrelatedAspects() {
		yield 'statement' => [ [ EntityUsage::STATEMENT_USAGE ] ];
		yield 'label' => [ [ EntityUsage::LABEL_USAGE ] ];
		yield 'other' => [ [ EntityUsage::OTHER_USAGE ] ];
		yield 'sitelink' => [ [ EntityUsage::SITELINK_USAGE ] ];
		yield 'title' => [ [ EntityUsage::TITLE_USAGE ] ];
		yield 'all' => [ [ EntityUsage::ALL_USAGE ] ];
	}

	public function testGetPagesUsing_filtersByLocalDescription() {
		$globalSiteId = 'wiki';
		$aspects = [ EntityUsage::DESCRIPTION_USAGE ];
		$entityIds = [
			// linked to a page without local description (should have implicit usage)
			new ItemId( 'Q1234' ),
			// linked to a page with local description (should not have implicit usage)
			new ItemId( 'Q5678' ),
		];
		$siteLinkLookup = $this->createMock( SiteLinkLookup::class );
		$siteLinkLookup->method( 'getLinks' )
			->with( [ 1234, 5678 ], [ $globalSiteId ] )
			->willReturn( [
				[ $globalSiteId, 'Page 1234', $entityIds[0]->getNumericId() ],
				[ $globalSiteId, 'Page 5678', $entityIds[1]->getNumericId() ],
			] );
		$descriptionLookup = $this->createMock( DescriptionLookup::class );
		$descriptionLookup->expects( $this->once() )
			->method( 'getDescriptions' )
			->with( $this->callback( function ( array $titles ) {
				$this->assertCount( 2, $titles );
				$this->assertSame( 1234, $titles[0]->getArticleID() );
				$this->assertSame( 5678, $titles[1]->getArticleID() );
				return true;
			} ), DescriptionLookup::SOURCE_LOCAL )
			->willReturn( [ 5678 => 'local description' ] );
		$titleFactory = $this->mockTitleFactory();
		$linkBatchFactory = $this->mockLinkBatchFactory( [ 1234, 5678 ] );
		$usageLookup = $this->createMock( UsageLookup::class );
		$usageLookup->method( 'getPagesUsing' )
			->with( $entityIds, $aspects )
			->willReturn( new ArrayIterator( [] ) );

		$pageEntityUsages = ( new ImplicitDescriptionUsageLookup(
			$usageLookup,
			$titleFactory,
			true,
			$descriptionLookup,
			$linkBatchFactory,
			$globalSiteId,
			$siteLinkLookup
		) )->getPagesUsing( $entityIds, $aspects );

		$pageEntityUsages = iterator_to_array( $pageEntityUsages );
		$this->assertCount( 1, $pageEntityUsages );
		/** @var PageEntityUsages $pageEntityUsages1234 */
		'@phan-var PageEntityUsages $pageEntityUsages1234';
		$pageEntityUsages1234 = $pageEntityUsages[0];
		$this->assertSame( 1234, $pageEntityUsages1234->getPageId() );
		$usages1234 = array_values( $pageEntityUsages1234->getUsages() );
		$this->assertCount( 1, $usages1234 );
		$implicitUsage = $usages1234[0];
		$this->assertEquals( $entityIds[0], $implicitUsage->getEntityId() );
		$this->assertSame( EntityUsage::DESCRIPTION_USAGE, $implicitUsage->getAspect() );
		$this->assertSame( 'lang-x-1234', $implicitUsage->getModifier() );
	}

	public function testGetUnusedEntities() {
		$entityIds = [ new ItemId( 'Q123' ) ];
		$expectedUsages = [ 'opaque sentinel' ];
		$usageLookup = $this->createMock( UsageLookup::class );
		$usageLookup->method( 'getUnusedEntities' )
			->with( $entityIds )
			->willReturn( $expectedUsages );

		$actualUsages = ( new ImplicitDescriptionUsageLookup(
			$usageLookup,
			$this->createMock( TitleFactory::class ),
			false,
			$this->createMock( DescriptionLookup::class ),
			$this->createMock( LinkBatchFactory::class ),
			'wiki',
			$this->createMock( SiteLinkLookup::class )
		) )->getUnusedEntities( $entityIds );

		$this->assertSame( $expectedUsages, $actualUsages );
	}

	/**
	 * Create a mock {@link TitleFactory}, returning titles with a page ID
	 * matching the page title, and page language code lang-x-pageID:
	 * For example, title Page 123 = page ID 123 = language lang-x-123.
	 */
	private function mockTitleFactory(): TitleFactory {
		$titleFactory = $this->createMock( TitleFactory::class );
		$titleFactory->method( 'newFromDBkey' )
			->willReturnCallback( function ( $pageName ) {
				if ( strpos( $pageName, 'Page ' ) === 0 ) {
					$pageId = (int)substr( $pageName, 5 );
				} else {
					$pageId = 0;
				}
				$title = $this->createMock( Title::class );
				$title->method( 'getArticleID' )
					->willReturn( $pageId );
				$language = $this->createMock( Language::class );
				$language->method( 'getCode' )
					->willReturn( "lang-x-$pageId" );
				$title->method( 'getPageLanguage' )
					->willReturn( $language );
				return $title;
			} );
		return $titleFactory;
	}

	/**
	 * Create a mock {@link LinkBatchFactory}, which asserts that
	 * {@link LinkBatchFactory::newLinkBatch()} is called with the expected page IDs
	 * and then returns a link batch that does nothing on execute.
	 * @param int[] $expectedPageIds
	 */
	private function mockLinkBatchFactory( array $expectedPageIds ): LinkBatchFactory {
		$linkBatchFactory = $this->createMock( LinkBatchFactory::class );
		$linkBatchFactory->expects( $this->once() )
			->method( 'newLinkBatch' )
			->willReturnCallback( function ( array $titles ) use ( $expectedPageIds ) {
				// assert that it’s called with the right (mocked) titles
				$pageIds = [];
				foreach ( $titles as $title ) {
					$pageIds[] = $title->getArticleID();
				}
				$this->assertSame( $expectedPageIds, $pageIds );

				$linkBatch = $this->createMock( LinkBatch::class );
				$linkBatch->expects( $this->once() )
					->method( 'execute' );
				return $linkBatch;
			} );
		return $linkBatchFactory;
	}

}
