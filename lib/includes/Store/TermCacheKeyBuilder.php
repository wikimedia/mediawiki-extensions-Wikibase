<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
trait TermCacheKeyBuilder {

	public function buildCacheKey( EntityId $id, int $revision, string $language, string $termType ) {
		return str_replace(
			[ '{', '}', '(', ')', '/', '\\', '@', ':' ],
			'_',
			"{$id->getSerialization()}_{$revision}_{$language}_{$termType}"
		);
	}

}
