<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SitelinkTargetNotFound;

/**
 * @license GPL-2.0-or-later
 */
interface SitelinkTargetTitleResolver {

	/**
	 * Resolves redirects unless the provided badges contain a redirect badge.
	 *
	 * @throws SitelinkTargetNotFound if the title does not exist
	 */
	public function resolveTitle( string $siteId, string $title, array $badges ): string;

}
