<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use MediaWiki\Site\Site;
use MediaWiki\Site\SiteLookup;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLinks;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\SiteLinkLookupSiteLinksRetriever;
use Wikibase\Repo\RestApi\Infrastructure\SiteLinksReadModelConverter;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\SiteLinkLookupSiteLinksRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkLookupSiteLinksRetrieverTest extends TestCase {

	private SiteLinkLookup $siteLinkLookup;
	private SiteLinksReadModelConverter $siteLinksReadModelConverter;

	protected function setUp(): void {
		parent::setUp();

		$this->siteLinkLookup = $this->createStub( SiteLinkLookup::class );

		$someSite = new Site();
		$someSite->setLinkPath( 'https://some-wiki.example/$1' );

		$siteLookup = $this->createStub( SiteLookup::class );
		$siteLookup->method( 'getSite' )->willReturn( $someSite );

		$this->siteLinksReadModelConverter = new SiteLinksReadModelConverter( $siteLookup );
	}

	public function testGetSiteLinks(): void {
		$itemId = new ItemId( 'Q123' );
		$siteId = 'dewiki';
		$pageName = 'Kartoffel';

		$siteLinksArray = [ new SiteLink( $siteId, $pageName ) ];

		$this->siteLinkLookup = $this->newSiteLinkLookupForIdWithReturnValue( $itemId, $siteLinksArray );

		$siteLinks = $this->newRetriever()->getSiteLinks( $itemId );

		$this->assertEquals( $this->siteLinksReadModelConverter->convert( new SiteLinkList( $siteLinksArray ) ), $siteLinks );
	}

	public function testGivenItemHasNoSiteLinks_returnsEmptySiteLinks(): void {
		$itemId = new ItemId( 'Q123' );

		$this->siteLinkLookup = $this->newSiteLinkLookupForIdWithReturnValue( $itemId, [] );

		$siteLinks = $this->newRetriever()->getSiteLinks( $itemId );

		$this->assertEquals( new SiteLinks(), $siteLinks );
	}

	private function newSiteLinkLookupForIdWithReturnValue( ItemId $id, array $returnValue ): SiteLinkLookup {
		$siteLinkLookup = $this->createMock( SiteLinkLookup::class );
		$siteLinkLookup->expects( $this->once() )
			->method( 'getSiteLinksForItem' )
			->with( $id )
			->willReturn( $returnValue );

		return $siteLinkLookup;
	}

	private function newRetriever(): SiteLinkLookupSiteLinksRetriever {
		return new SiteLinkLookupSiteLinksRetriever(
			$this->siteLinkLookup,
			$this->siteLinksReadModelConverter
		);
	}

}
