<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\LanguageFallbackChain;

/**
 * A builder for collecting information about a batch of entities.
 *
 * @license GPL-2.0-or-later
 */
interface EntityInfoBuilder {

	/**
	 * TODO: rename to getEntityInfo
	 *
	 * @param EntityId[] $entityIds
	 * @param LanguageFallbackChain $languageFallbackChain
	 *
	 * @return EntityInfo
	 */
	public function collectEntityInfo( array $entityIds, LanguageFallbackChain $languageFallbackChain );

}
