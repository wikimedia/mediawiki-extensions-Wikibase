<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use PHPUnit\Framework\TestCase;
use Site;
use SiteLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLink as SiteLinkReadModel;
use Wikibase\Repo\RestApi\Infrastructure\SiteLinksReadModelConverter;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\SiteLinksReadModelConverter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinksReadModelConverterTest extends TestCase {

	private const EN_WIKI_URL_PREFIX = 'https://en.wikipedia.org/wiki/';
	private const DE_WIKI_URL_PREFIX = 'https://de.wikipedia.org/wiki/';

	public function testConvert(): void {
		$enSiteLink = new SiteLink( 'enwiki', 'potato' );
		$deSiteLink = new SiteLink( 'dewiki', 'Kartoffel', [ new ItemId( 'Q123' ) ] );

		$readModel = $this->newConverter()->convert(
			new SiteLinkList( [ $enSiteLink, $deSiteLink ] )
		);

		$this->assertEquals(
			new SiteLinkReadModel(
				$enSiteLink->getSiteId(),
				$enSiteLink->getPageName(),
				$enSiteLink->getBadges(),
				self::EN_WIKI_URL_PREFIX . $enSiteLink->getPageName()
			),
			$readModel[0]
		);
		$this->assertEquals(
			new SiteLinkReadModel(
				$deSiteLink->getSiteId(),
				$deSiteLink->getPageName(),
				$deSiteLink->getBadges(),
				self::DE_WIKI_URL_PREFIX . $deSiteLink->getPageName()
			),
			$readModel[1]
		);
	}

	private function newConverter(): SiteLinksReadModelConverter {
		$enSite = new Site();
		$enSite->setLinkPath( self::EN_WIKI_URL_PREFIX . '$1' );
		$deSite = new Site();
		$deSite->setLinkPath( self::DE_WIKI_URL_PREFIX . '$1' );

		$siteLookup = $this->createStub( SiteLookup::class );
		$siteLookup->method( 'getSite' )->willReturnMap( [
			[ 'enwiki', $enSite ],
			[ 'dewiki', $deSite ],
		] );

		return new SiteLinksReadModelConverter( $siteLookup );
	}

}
