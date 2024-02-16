<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use SiteLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SitelinkTargetNotFound;
use Wikibase\Repo\RestApi\Domain\Services\SitelinkTargetTitleResolver;
use Wikibase\Repo\SiteLinkPageNormalizer;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinkPageNormalizerSitelinkTargetResolver implements SitelinkTargetTitleResolver {

	private SiteLookup $siteLookup;
	private SiteLinkPageNormalizer $siteLinkPageNormalizer;

	public function __construct( SiteLookup $siteLookup, SiteLinkPageNormalizer $siteLinkPageNormalizer ) {
		$this->siteLookup = $siteLookup;
		$this->siteLinkPageNormalizer = $siteLinkPageNormalizer;
	}

	public function resolveTitle( string $siteId, string $title, array $badges ): string {
		$resolvedTitleTarget = $this->siteLinkPageNormalizer->normalize(
			$this->siteLookup->getSite( $siteId ),
			$title,
			array_map( fn( ItemId $itemId ) => $itemId->getSerialization(), $badges )
		);

		if ( $resolvedTitleTarget === false ) {
			throw new SitelinkTargetNotFound();
		}

		return $resolvedTitleTarget;
	}

}
