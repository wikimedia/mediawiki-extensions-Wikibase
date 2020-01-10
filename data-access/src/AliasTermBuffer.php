<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Entity\EntityId;

/**
 * AliasTermBuffer Interface, to be used alongside TermBuffer.
 *
 * @todo This or something similar should perhaps move to data-model-services
 *
 * @license GPL-2.0-or-later
 */
interface AliasTermBuffer {

	/**
	 * Returns terms that were previously loaded by prefetchTerms.
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @return string[]|false|null The aliases, or false of that entity has no aliases,
	 *         or null if the term was not yet requested via prefetchTerms().
	 */
	public function getPrefetchedAliases( EntityId $entityId, $languageCode );

}
