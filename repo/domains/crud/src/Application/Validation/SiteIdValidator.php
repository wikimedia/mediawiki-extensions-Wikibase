<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Validation;

use Wikibase\Repo\Domains\Crud\Domain\Services\SiteIdsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class SiteIdValidator {

	public const CODE_INVALID_SITE_ID = 'site-id-validator-code-invalid-site-id';
	public const CONTEXT_SITE_ID_VALUE = 'site-id-validator-context-site-id-value';

	public function __construct( private readonly SiteIdsRetriever $siteIdsRetriever ) {
	}

	public function validate( string $siteId ): ?ValidationError {
		return in_array( $siteId, $this->siteIdsRetriever->getValidSiteIds() )
			? null
			: new ValidationError(
				self::CODE_INVALID_SITE_ID,
				[ self::CONTEXT_SITE_ID_VALUE => $siteId ]
			);
	}

}
