<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Exception;
use HashSiteStore;
use PHPUnit4And6Compat;
use Site;
use Wikibase\Repo\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinksChangeOpDeserializer;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\SiteLinksChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinksChangeOpDeserializerTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	const SITE_ID = 'somewiki';

	const SITE_LINK_TITLE = 'Some Article';

	const BADGE_ITEM_ID = 'Q3000';

	/**
	 * @return SiteLinkBadgeChangeOpSerializationValidator
	 */
	private function newBadgeSerializationValidator() {
		$lookup = $this->getMockBuilder( SiteLinkBadgeChangeOpSerializationValidator::class )
			->disableOriginalConstructor()
			->getMock();

		return $lookup;
	}

	private function newSiteLinkTargetProvider() {
		$wiki = new Site();
		$wiki->setGlobalId( self::SITE_ID );
		$wiki->setGroup( 'testwikis' );

		return new SiteLinkTargetProvider( new HashSiteStore( [ $wiki ] ) );
	}

	private function newSiteLinksChangeOpDeserializer() {
		return new SiteLinksChangeOpDeserializer(
			$this->newBadgeSerializationValidator(),
			new SiteLinkChangeOpFactory( [ self::BADGE_ITEM_ID ] ),
			$this->newSiteLinkTargetProvider(),
			new ItemIdParser(),
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
		$badgeValidator = $this->getMockBuilder( SiteLinkBadgeChangeOpSerializationValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$badgeValidator->method( $this->anything() )
			->will( $this->throwException(
				new ChangeOpDeserializationException( 'invalid badge data', 'test-badge-error' )
			) );

		$deserializer = new SiteLinksChangeOpDeserializer(
			$badgeValidator,
			new SiteLinkChangeOpFactory( [] ),
			$this->newSiteLinkTargetProvider(),
			new ItemIdParser(),
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
		$site = $this->getMock( Site::class );
		$site->method( 'getGlobalId' )
			->will( $this->returnValue( self::SITE_ID ) );
		$site->method( 'getGroup' )
			->will( $this->returnValue( 'testwikis' ) );
		$site->method( 'getNavigationIds' )
			->will( $this->returnValue( [] ) );
		$site->method( 'normalizePageName' )
			->will( $this->returnValue( false ) );

		$deserializer = new SiteLinksChangeOpDeserializer(
			$this->newBadgeSerializationValidator(),
			new SiteLinkChangeOpFactory( [] ),
			new SiteLinkTargetProvider( new HashSiteStore( [ $site ] ) ),
			new ItemIdParser(),
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
		$item = new Item();

		$item->setSiteLinkList( new SiteLinkList( [ new SiteLink( self::SITE_ID, self::SITE_LINK_TITLE ) ] ) );

		return $item;
	}

	private function getItemWithSiteLinkWithBadges() {
		$item = new Item();

		$item->setSiteLinkList( new SiteLinkList( [
			new SiteLink( self::SITE_ID, self::SITE_LINK_TITLE, [ new ItemId( self::BADGE_ITEM_ID ) ] )
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

		$deserializer = $this->newSiteLinksChangeOpDeserializer();

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

		$deserializer = $this->newSiteLinksChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'badges' => [ self::BADGE_ITEM_ID ] ] ] ]
		);

		$changeOp->apply( $item );

		$this->assertTrue(
			$item->getSiteLinkList()->getBySiteId( self::SITE_ID )->equals(
				new SiteLink( self::SITE_ID, self::SITE_LINK_TITLE, [ new ItemId( self::BADGE_ITEM_ID ) ] )
			)
		);
	}

	public function testGivenSiteAndBadgesAndSiteLinkForSiteExistsWithBadges_changeOpSetsNewSetOfBadges() {
		$item = $this->getItemWithSiteLinkWithBadges();

		$newBadgeId = 'Q4000';

		$deserializer = new SiteLinksChangeOpDeserializer(
			$this->newBadgeSerializationValidator(),
			new SiteLinkChangeOpFactory( [ self::BADGE_ITEM_ID, $newBadgeId ] ),
			$this->newSiteLinkTargetProvider(),
			new ItemIdParser(),
			new StringNormalizer(),
			[ 'testwikis' ]
		);

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'badges' => [ $newBadgeId ] ] ] ]
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

		$deserializer = $this->newSiteLinksChangeOpDeserializer();

		$changeOp = $deserializer->createEntityChangeOp(
			[ 'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'badges' => [] ] ] ]
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
