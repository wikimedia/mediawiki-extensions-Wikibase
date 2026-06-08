<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\Controllers;

/**
 * @license GPL-2.0-or-later
 */
class WbSearchEntitiesRequest {

	public function __construct(
		public readonly string $text,
		public readonly string $searchLanguageCode,
		/** not all controllers take $resultLanguage into account yet, see T423217 */
		public readonly string $resultLanguage,
		public readonly int $limit,
		public readonly bool $strictLanguage,
		public readonly ?string $profileContext,
		public readonly ?string $username,
	) {
	}
}
