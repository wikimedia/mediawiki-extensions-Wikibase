<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Exception;
use HashSiteStore;
use Site;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\ChangeOp\ChangeOpSiteLink;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinksChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\Repo\SiteLinkPageNormalizer;
use Wikibase\Repo\SiteLinkTargetProvider;

/**
 * @covers \Wikibase\Repo\ChangeOp\Deserialization\SiteLinksChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinksChangeOpDeserializerTest extends \PHPUnit\Framework\TestCase {

	private const SITE_ID = 'somewiki';

	private const SITE_LINK_TITLE = 'Some Article';

	private const BADGE_ITEM_ID = 'Q3000';

	private function newSiteLinkTargetProvider() {
		$wiki = new Site();
		$wiki->setGlobalId( self::SITE_ID );
		$wiki->setGroup( 'testwikis' );

		return new SiteLinkTargetProvider( new HashSiteStore( [ $wiki ] ) );
	}

	private function newSiteLinksChangeOpDeserializer( Item $item = null ): SiteLinksChangeOpDeserializer {
		$entityLookupMock = $this->createStub( EntityLookup::class );
		if ( $item !== null ) {
			$entityLookupMock->method( 'getEntity' )
				->with( $this->equalTo( $item->getId() ) )
				->willReturn( $item );
		}

		return new SiteLinksChangeOpDeserializer(
			$this->createMock( SiteLinkBadgeChangeOpSerializationValidator::class ),
			new SiteLinkChangeOpFactory( [ self::BADGE_ITEM_ID ] ),
			new SiteLinkPageNormalizer( [] ),
			$this->newSiteLinkTargetProvider(),
			new ItemIdParser(),
			$entityLookupMock,
			new StringNormalizer(),
			[ 'testwikis' ]
		);
	}

	public function testGivenSitelinksNotAnArray_createEntityChangeOpThrowsException() {
		$deserializer = $this->newSiteLinksChangeOpDeserializer();

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $deserializer ) {
				$deserializer->createEntityChangeOp( [ 'sitelinks' => 'foo' ] );
			},
			'not-recognized-array'
		);
	}

	public function provideInvalidSerialization() {
		return [
			'serialization is not an array' => [ 'BAD', 'not-recognized-array' ],
			'no site in serialization' => [ [], 'no-site' ],
			'site is not a string' => [ [ 'site' => 1200 ], 'not-recognized-string' ],
			'title is not a string' => [ [ 'site' => self::SITE_ID, 'title' => 4000 ], 'not-recognized-string' ],
			'badges is not an array' => [ [ 'site' => self::SITE_ID, 'badges' => 'BAD' ], 'not-recognized-array' ],
		];
	}

	/**
	 * @dataProvider provideInvalidSerialization
	 */
	public function testGivenInvalidSerializationData_createEntityChangeOpThrowsException( $serialization, $expectedError ) {
		$deserializer = $this->newSiteLinksChangeOpDeserializer();

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $deserializer, $serialization ) {
				$deserializer->createEntityChangeOp( [ 'sitelinks' => [ self::SITE_ID => $serialization ] ] );
			},
			$expectedError
		);
	}

	public function testGivenSiteIsInconsistent_createEntityChangeOpThrowsException() {
		$deserializer = $this->newSiteLinksChangeOpDeserializer();

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $deserializer ) {
				$deserializer->createEntityChangeOp( [ 'sitelinks' => [ self::SITE_ID => [ 'site' => 'someotherwiki' ] ] ] );
			},
			'inconsistent-site'
		);
	}

	public function testGivenSiteNotInValidSiteList_createEntityChangeOpThrowsException() {
		$deserializer = $this->newSiteLinksChangeOpDeserializer();

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $deserializer ) {
				$deserializer->createEntityChangeOp( [ 'sitelinks' => [ 'someotherwiki' => [ 'site' => 'someotherwiki' ] ] ] );
			},
			'not-recognized-site'
		);
	}

	public function testGivenInvalidBadgesData_createEntityChangeOpThrowsException() {
		$badgeValidator = $this->createMock( SiteLinkBadgeChangeOpSerializationValidator::class );

		$badgeValidator->method( $this->anything() )
			->willThrowException(
				new ChangeOpDeserializationException( 'invalid badge data', 'test-badge-error' )
			);

		$deserializer = new SiteLinksChangeOpDeserializer(
			$badgeValidator,
			new SiteLinkChangeOpFactory( [] ),
			new SiteLinkPageNormalizer( [] ),
			$this->newSiteLinkTargetProvider(),
			new ItemIdParser(),
			$this->createMock( EntityLookup::class ),
			new StringNormalizer(),
			[ 'testwikis' ]
		);

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $deserializer ) {
				$deserializer->createEntityChangeOp(
					[ 'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'badges' => [ 'BAD' ] ] ] ]
				);
			},
			'test-badge-error'
		);
	}

	public function testGivenTitleNotFoundOnSite_createEntityChangeOpThrowsException() {
		$site = $this->createMock( Site::class );
		$site->method( 'getGlobalId' )
			->willReturn( self::SITE_ID );
		$site->method( 'getGroup' )
			->willReturn( 'testwikis' );
		$site->method( 'getNavigationIds' )
			->willReturn( [] );
		$site->method( 'normalizePageName' )
			->willReturn( false );

		$deserializer = new SiteLinksChangeOpDeserializer(
			$this->createMock( SiteLinkBadgeChangeOpSerializationValidator::class ),
			new SiteLinkChangeOpFactory( [] ),
			new SiteLinkPageNormalizer( [] ),
			new SiteLinkTargetProvider( new HashSiteStore( [ $site ] ) ),
			new ItemIdParser(),
			$this->createMock( EntityLookup::class ),
			new StringNormalizer(),
			[ 'testwikis' ]
		);

		$exception = null;

		try {
			$deserializer->createEntityChangeOp(
				[ 'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'title' => 'Some Random Page' ] ] ]
			);
		} catch ( Exception $ex ) {
			$exception = $ex;
		}

		$this->assertInstanceOf( ChangeOpDeserializationException::class, $exception );
		$this->assertSame( 'no-external-page', $exception->getErrorCode() );
		$this->assertEquals( [ 'somewiki', 'Some Random Page' ], $exception->getParams() );
	}

	private function getItemWithoutSiteLinks() {
		return new Item();
	}

	private function getItemWithSiteLink() {
		$item = new Item( new ItemId( 'Q42' ) );

		$item->setSiteLinkList( new SiteLinkList( [ new SiteLink( self::SITE_ID, self::SITE_LINK_TITLE ) ] ) );

		return $item;
	}

	private function getItemWithSiteLinkWithBadges() {
		$item = new Item( new ItemId( 'Q42' ) );

		$item->setSiteLinkList( new SiteLinkList( [
			new SiteLink( self::SITE_ID, self::SITE_LINK_TITLE, [ new ItemId( self::BADGE_ITEM_ID ) ] ),
		] ) );

		return $item;
	}

	public function testGivenSiteAndTitleAndNoSiteLinkForSite_changeOpAddsSiteLink() {
		$item = $this->getItemWithoutSiteLinks();

		$pageTitle = 'Cool Article';

		$deserializer = $this->newSiteLinksChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'title' => $pageTitle ] ] ]
		);

		$changeOp->apply( $item );

		$this->assertTrue(
			$item->getSiteLinkList()->getBySiteId( self::SITE_ID )->equals(
				new SiteLink( self::SITE_ID, $pageTitle )
			)
		);
	}

	public function testGivenSiteAndTitleAndNoSiteLinkForSiteWithNumericKey_changeOpAddsSiteLink() {
		$item = $this->getItemWithoutSiteLinks();

		$pageTitle = 'Cool Article';

		$deserializer = $this->newSiteLinksChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'sitelinks' => [ 11 => [ 'site' => self::SITE_ID, 'title' => $pageTitle ] ] ]
		);

		$changeOp->apply( $item );

		$this->assertTrue(
			$item->getSiteLinkList()->getBySiteId( self::SITE_ID )->equals(
				new SiteLink( self::SITE_ID, $pageTitle )
			)
		);
	}

	public function testGivenSiteAndTitleAndBadgeAndNoSiteLinkForSite_changeOpAddsSiteLinkWithBadge() {
		$item = $this->getItemWithoutSiteLinks();

		$pageTitle = 'Cool Article';

		$pageNormalizer = $this->createMock( SiteLinkPageNormalizer::class );
		$pageNormalizer->expects( $this->once() )->method( 'normalize' )
			->with(
				$this->anything(),
				$this->equalTo( $pageTitle ),
				$this->equalTo( [ self::BADGE_ITEM_ID ] )
			)
			->willReturn( $pageTitle );
		$deserializer = new SiteLinksChangeOpDeserializer(
			$this->createMock( SiteLinkBadgeChangeOpSerializationValidator::class ),
			new SiteLinkChangeOpFactory( [ self::BADGE_ITEM_ID ] ),
			$pageNormalizer,
			$this->newSiteLinkTargetProvider(),
			new ItemIdParser(),
			$this->createMock( EntityLookup::class ),
			new StringNormalizer(),
			[ 'testwikis' ]
		);

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'title' => $pageTitle, 'badges' => [ self::BADGE_ITEM_ID ] ] ] ]
		);

		$changeOp->apply( $item );

		$this->assertTrue(
			$item->getSiteLinkList()->getBySiteId( self::SITE_ID )->equals(
				new SiteLink( self::SITE_ID, $pageTitle, [ new ItemId( self::BADGE_ITEM_ID ) ] )
			)
		);
	}

	public function testGivenSiteAndTitleAndSiteLinkForSiteExists_changeOpSetsNewLinkTitle() {
		$item = $this->getItemWithSiteLink();

		$newLinkTitle = 'Cool Article';

		$deserializer = $this->newSiteLinksChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'title' => $newLinkTitle ] ] ]
		);

		$changeOp->apply( $item );

		$this->assertTrue(
			$item->getSiteLinkList()->getBySiteId( self::SITE_ID )->equals(
				new SiteLink( self::SITE_ID, $newLinkTitle )
			)
		);
	}

	public function testGivenSiteAndBadgesAndSiteLinkForSiteExistsWithNoBadges_changeOpAddsBadge() {
		$item = $this->getItemWithSiteLink();
		$entityLookupMock = $this->createMock( EntityLookup::class );
		$entityLookupMock->expects( $this->once() )->method( 'getEntity' )
			->with( $this->equalTo( $item->getId() ) )
			->willReturn( $item );

		$siteLinkChangeOpFactoryMock = $this->createMock( SiteLinkChangeOpFactory::class );
		$siteLinkChangeOpFactoryMock->expects( $this->once() )->method( 'newSetSiteLinkOp' )->with(
			$this->equalTo( self::SITE_ID ),
			$this->equalTo( self::SITE_LINK_TITLE ),
			$this->equalTo( [ new ItemId( self::BADGE_ITEM_ID ) ] )
		)->willReturn( new ChangeOpSiteLink( self::SITE_ID, self::SITE_LINK_TITLE, [ new ItemId( self::BADGE_ITEM_ID ) ] ) );

		$deserializer = new SiteLinksChangeOpDeserializer(
			$this->createMock( SiteLinkBadgeChangeOpSerializationValidator::class ),
			$siteLinkChangeOpFactoryMock,
			new SiteLinkPageNormalizer( [] ),
			$this->newSiteLinkTargetProvider(),
			new ItemIdParser(),
			$entityLookupMock,
			new StringNormalizer(),
			[ 'testwikis' ]
		);

		$changeOp = $deserializer->createEntityChangeOp(
			[
				'id' => $item->getId()->getSerialization(),
				'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'badges' => [ self::BADGE_ITEM_ID ] ] ],
			]
		);

		$changeOp->apply( $item );

		$this->assertTrue(
			$item->getSiteLinkList()->getBySiteId( self::SITE_ID )->equals(
				new SiteLink( self::SITE_ID, self::SITE_LINK_TITLE, [ new ItemId( self::BADGE_ITEM_ID ) ] )
			)
		);
	}

	public function testGivenSiteAndBadgesAndSiteLinkForSiteNotExists_returnsError(): void {
		$item = $this->getItemWithoutSiteLinks();
		$item->setId( new ItemId( 'Q5' ) );

		$deserializer = $this->newSiteLinksChangeOpDeserializer( $item );

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function () use ( $deserializer, $item ) {
				$deserializer->createEntityChangeOp(
					[
						'id' => $item->getId()->getSerialization(),
						'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'badges' => [ self::BADGE_ITEM_ID ] ] ],
					]
				);
			},
			'no-such-sitelink'
		);
	}

	public function testGivenSiteAndBadgesAndSiteLinkForSiteExistsWithBadges_changeOpSetsNewSetOfBadges() {
		$item = $this->getItemWithSiteLinkWithBadges();
		$entityLookup = $this->createStub( EntityLookup::class );
		$entityLookup->expects( $this->once() )->method( 'getEntity' )
			->willReturn( $item );

		$newBadgeId = 'Q4000';

		$deserializer = new SiteLinksChangeOpDeserializer(
			$this->createMock( SiteLinkBadgeChangeOpSerializationValidator::class ),
			new SiteLinkChangeOpFactory( [ self::BADGE_ITEM_ID, $newBadgeId ] ),
			new SiteLinkPageNormalizer( [] ),
			$this->newSiteLinkTargetProvider(),
			new ItemIdParser(),
			$entityLookup,
			new StringNormalizer(),
			[ 'testwikis' ]
		);

		$changeOp = $deserializer->createEntityChangeOp(
			[
				'id' => $item->getId()->getSerialization(),
				'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'badges' => [ $newBadgeId ] ] ],
			]
		);

		$changeOp->apply( $item );

		$this->assertTrue(
			$item->getSiteLinkList()->getBySiteId( self::SITE_ID )->equals(
				new SiteLink( self::SITE_ID, self::SITE_LINK_TITLE, [ new ItemId( $newBadgeId ) ] )
			)
		);
	}

	public function testGivenSiteAndEmptyBadgesAndSiteLinkForSiteExistsWithBadges_changeOpClearsBadges() {
		$item = $this->getItemWithSiteLinkWithBadges();

		$deserializer = $this->newSiteLinksChangeOpDeserializer( $item );

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'id' => $item->getId()->getSerialization(), 'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'badges' => [] ] ] ]
		);

		$changeOp->apply( $item );

		$this->assertTrue(
			$item->getSiteLinkList()->getBySiteId( self::SITE_ID )->equals(
				new SiteLink( self::SITE_ID, self::SITE_LINK_TITLE, [] )
			)
		);
	}

	public function testGivenSiteAndRemoveKeyAndSiteLinkForSiteExists_changeOpRemovesSiteLink() {
		$item = $this->getItemWithSiteLink();

		$deserializer = $this->newSiteLinksChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'remove' => '' ] ] ]
		);

		$changeOp->apply( $item );

		$this->assertFalse( $item->getSiteLinkList()->hasLinkWithSiteId( self::SITE_ID ) );
	}

	public function testGivenSiteAndEmptyTitleAndSiteLinkForSiteExists_changeOpRemovesSiteLink() {
		$item = $this->getItemWithSiteLink();

		$deserializer = $this->newSiteLinksChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'title' => '' ] ] ]
		);

		$changeOp->apply( $item );

		$this->assertFalse( $item->getSiteLinkList()->hasLinkWithSiteId( self::SITE_ID ) );
	}

	public function testGivenSiteAndNoTitleNorBadgesAndSiteLinkForSiteExists_changeOpRemovesSiteLink() {
		$item = $this->getItemWithSiteLink();

		$deserializer = $this->newSiteLinksChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID ] ] ]
		);

		$changeOp->apply( $item );

		$this->assertFalse( $item->getSiteLinkList()->hasLinkWithSiteId( self::SITE_ID ) );
	}

}
