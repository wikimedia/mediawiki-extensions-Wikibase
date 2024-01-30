<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use MediaWiki\Site\SiteLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelink as SitelinkReadModel;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelinks;

/**
 * @license GPL-2.0-or-later
 */
class SitelinksReadModelConverter {

	private SiteLookup $siteLookup;

	public function __construct( SiteLookup $siteLookup ) {
		$this->siteLookup = $siteLookup;
	}

	public function convert( SiteLinkList $sitelinkList ): Sitelinks {
		return new Sitelinks(
			...array_map(
				fn( SiteLink $sitelink ) => new SitelinkReadModel(
					$sitelink->getSiteId(),
					$sitelink->getPageName(),
					$sitelink->getBadges(),
					$this->buildUrl( $sitelink->getSiteId(), $sitelink->getPageName() )
				),
				array_values( $sitelinkList->toArray() )
			)
		);
	}

	private function buildUrl( string $siteId, string $title ): string {
		return $this->siteLookup->getSite( $siteId )->getPageUrl( $title );
	}

}
