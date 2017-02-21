<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use HashSiteStore;
use Site;
use Wikibase\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinksChangeOpDeserializer;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\SiteLinksChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class SiteLinksChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	const SITE_ID = 'somewiki';

	const SITE_LINK_TITLE = 'Some Article';

	const BADGE_ITEM_ID = 'Q3000';

	private function newSiteLinkBadgeLookup() {
		$lookup = $this->getMockBuilder( SiteLinkBadgeChangeOpSerializationValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$lookup->method( 'parseSiteLinkBadgesSerialization' )
			->will(
				$this->returnCallback( function( $badges ) {
					return array_map(
						function( $badgeId ) {
							return new ItemId( $badgeId );
						},
						$badges
					);
				} )
			);

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
			new SiteLinkChangeOpSerializationValidator( $this->newSiteLinkBadgeLookup() ),
			new SiteLinkChangeOpFactory( [ self::BADGE_ITEM_ID ] ),
			$this->newSiteLinkTargetProvider(),
			new ItemIdParser(),
			new StringNormalizer(),
			[ 'testwikis' ]
		);
	}

	public function testGivenSitelinksNotAnArray_createEntityChangeOpThrowsException() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$deserializer = $this->newSiteLinksChangeOpDeserializer();
				$deserializer->createEntityChangeOp( [ 'sitelinks' => 'foo' ] );
			},
			'not-recognized-array'
		);
	}

	public function testGivenInvalidSitelink_createEntityChangeOpThrowsException() {
		$validator = $this->getMockBuilder( SiteLinkChangeOpSerializationValidator::class )
			->disableOriginalConstructor()
			->getMock();
		$validator->method( $this->anything() )
			->will( $this->throwException(
				new ChangeOpDeserializationException( 'invalid sitelink serialization', 'test-error' )
			) );

		$deserializer = new SiteLinksChangeOpDeserializer(
			$validator,
			new SiteLinkChangeOpFactory( [] ),
			$this->newSiteLinkTargetProvider(),
			new ItemIdParser(),
			new StringNormalizer(),
			[ 'testwikis' ]
		);

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $deserializer ) {
				$deserializer->createEntityChangeOp( [ 'sitelinks' => [ 'foo' => [] ] ] );
			},
			'test-error'
		);
	}

	public function testGivenInvalidBadgesData_createEntityChangeOpThrowsException() {
		$badgeValidator = $this->getMockBuilder( SiteLinkBadgeChangeOpSerializationValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$badgeValidator->method( $this->anything() )
			->will( $this->throwException(
				new ChangeOpDeserializationException( 'invalid badge data', 'test-error' )
			) );

		$deserializer = new SiteLinksChangeOpDeserializer(
			new SiteLinkChangeOpSerializationValidator( $badgeValidator ),
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
			'test-error'
		);
	}

	public function testGivenUnknownSiteId_createEntityChangeOpThrowsException() {
		$validator = $this->getMockBuilder( SiteLinkChangeOpSerializationValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$deserializer = new SiteLinksChangeOpDeserializer(
			$validator,
			new SiteLinkChangeOpFactory( [] ),
			$this->newSiteLinkTargetProvider(),
			new ItemIdParser(),
			new StringNormalizer(),
			[ 'testwikis' ]
		);

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $deserializer ) {
				$deserializer->createEntityChangeOp( [ 'sitelinks' => [ 'foo' => [ 'site' => 'foo' ] ] ] );
			},
			'no-such-site'
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
			new SiteLinkChangeOpSerializationValidator( $this->newSiteLinkBadgeLookup() ),
			new SiteLinkChangeOpFactory( [] ),
			new SiteLinkTargetProvider( new HashSiteStore( [ $site ] ) ),
			new ItemIdParser(),
			new StringNormalizer(),
			[ 'testwikis' ]
		);

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $deserializer ) {
				$deserializer->createEntityChangeOp(
					[ 'sitelinks' => [ self::SITE_ID => [ 'site' => self::SITE_ID, 'title' => 'Some Random Page' ] ] ]
				);
			},
			'no-external-page'
		);
	}

	private function getItemWithoutSiteLinks() {
		return new Item();
	}

	private function getItemWithSiteLink() {
		$item = new Item();

		$item->setSiteLinkList( new SiteLinkList( [ new SiteLink( self::SITE_ID, self::SITE_LINK_TITLE ) ] ) );

		return $item;
	}

	private function getItemWithSiteLinkwithBadges() {
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
			new SiteLinkChangeOpSerializationValidator( $this->newSiteLinkBadgeLookup() ),
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
