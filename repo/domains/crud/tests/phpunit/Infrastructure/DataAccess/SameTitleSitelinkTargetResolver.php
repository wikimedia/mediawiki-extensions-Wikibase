<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess;

use Wikibase\Repo\Domains\Crud\Domain\Services\SitelinkTargetTitleResolver;

/**
 * @license GPL-2.0-or-later
 */
class SameTitleSitelinkTargetResolver implements SitelinkTargetTitleResolver {

	public function resolveTitle( string $siteId, string $title, array $badges ): string {
		return $title;
	}
}
