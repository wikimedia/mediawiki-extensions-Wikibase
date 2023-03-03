<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use SiteLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLink as SiteLinkReadModel;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLinks;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinksReadModelConverter {

	private SiteLookup $siteLookup;

	public function __construct( SiteLookup $siteLookup ) {
		$this->siteLookup = $siteLookup;
	}

	public function convert( SiteLinkList $siteLinkList ): SiteLinks {
		return new SiteLinks(
			...array_map(
				fn( SiteLink $siteLink ) => new SiteLinkReadModel(
					$siteLink->getSiteId(),
					$siteLink->getPageName(),
					$siteLink->getBadges(),
					$this->buildUrl( $siteLink->getSiteId(), $siteLink->getPageName() )
				),
				array_values( $siteLinkList->toArray() )
			)
		);
	}

	private function buildUrl( string $siteId, string $title ): string {
		$site = $this->siteLookup->getSite( $siteId );

		// Defaulting to '' here is a temporary hack in case the site doesn't have a URL configured. This shouldn't happen in reality but is
		// currently the case in our CI wiki.
		return $site->getPageUrl( $title ) ?? '';
	}

}
