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
		return $this->siteLookup->getSite( $siteId )->getPageUrl( $title );
	}

}
