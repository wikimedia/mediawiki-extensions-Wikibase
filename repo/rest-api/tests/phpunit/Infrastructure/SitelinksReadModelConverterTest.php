<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use MediaWiki\Site\Site;
use MediaWiki\Site\SiteLookup;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelink as SitelinkReadModel;
use Wikibase\Repo\RestApi\Infrastructure\SitelinksReadModelConverter;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\SitelinksReadModelConverter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SitelinksReadModelConverterTest extends TestCase {

	private const EN_WIKI_URL_PREFIX = 'https://en.wikipedia.org/wiki/';
	private const DE_WIKI_URL_PREFIX = 'https://de.wikipedia.org/wiki/';

	public function testConvert(): void {
		$enSitelink = new SiteLink( 'enwiki', 'potato' );
		$deSitelink = new SiteLink( 'dewiki', 'Kartoffel', [ new ItemId( 'Q123' ) ] );

		$readModel = $this->newConverter()->convert(
			new SiteLinkList( [ $enSitelink, $deSitelink ] )
		);

		$this->assertEquals(
			new SitelinkReadModel(
				$enSitelink->getSiteId(),
				$enSitelink->getPageName(),
				$enSitelink->getBadges(),
				self::EN_WIKI_URL_PREFIX . $enSitelink->getPageName()
			),
			$readModel[ 'enwiki' ]
		);
		$this->assertEquals(
			new SitelinkReadModel(
				$deSitelink->getSiteId(),
				$deSitelink->getPageName(),
				$deSitelink->getBadges(),
				self::DE_WIKI_URL_PREFIX . $deSitelink->getPageName()
			),
			$readModel[ 'dewiki' ]
		);
	}

	private function newConverter(): SitelinksReadModelConverter {
		$enSite = new Site();
		$enSite->setLinkPath( self::EN_WIKI_URL_PREFIX . '$1' );
		$deSite = new Site();
		$deSite->setLinkPath( self::DE_WIKI_URL_PREFIX . '$1' );

		$siteLookup = $this->createStub( SiteLookup::class );
		$siteLookup->method( 'getSite' )->willReturnMap( [
			[ 'enwiki', $enSite ],
			[ 'dewiki', $deSite ],
		] );

		return new SitelinksReadModelConverter( $siteLookup );
	}

}
