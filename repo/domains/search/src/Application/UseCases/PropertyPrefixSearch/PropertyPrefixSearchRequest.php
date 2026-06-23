<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch;

use Wikibase\Repo\Domains\Search\Domain\Model\User;

/**
 * @license GPL-2.0-or-later
 */
class PropertyPrefixSearchRequest {

	public const DEFAULT_LIMIT = 10;
	public const DEFAULT_OFFSET = 0;
	public readonly string $resultLanguage;

	public function __construct(
		public readonly string $query,
		public readonly string $language,
		public readonly User $user,
		public readonly int $limit = self::DEFAULT_LIMIT,
		public readonly int $offset = self::DEFAULT_OFFSET,
		public readonly bool $disableLanguageFallback = false,
		?string $resultLanguage = null,
		public readonly bool $disableLimitValidation = false,
	) {
		$this->resultLanguage = $resultLanguage ?? $language;
	}

}
