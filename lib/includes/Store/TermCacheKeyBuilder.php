<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
trait TermCacheKeyBuilder {

	public function buildCacheKey( EntityId $id, int $revision, string $language, string $termType ): string {
		return str_replace(
			[ '{', '}', '(', ')', '/', '\\', '@', ':' ],
			'_',
			// For property cache entries, $revision here will always be 0, since we want to avoid
			// looking up revision ids for properties (T434204)
			"{$id->getSerialization()}_{$revision}_{$language}_{$termType}"
		);
	}

}
