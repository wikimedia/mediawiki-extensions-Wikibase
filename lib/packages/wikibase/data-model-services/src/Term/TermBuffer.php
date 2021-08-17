<?php

namespace Wikibase\DataModel\Services\Term;

use Wikibase\DataModel\Entity\EntityId;

/**
 * A service interface for buffering terms.
 *
 * Typically implemented in conjunction with {@link TermLookup},
 * and (like that interface) does not apply language fallbacks.
 *
 * @since 1.1
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface TermBuffer {

	/**
	 * Loads a set of terms into the buffer.
	 * The source from which to fetch would typically be supplied to the buffer's constructor.
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $termTypes The desired term types,
	 * i.e. any of TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION, TermTypes::TYPE_ALIAS
	 * @param string[] $languageCodes The desired languages.
	 */
	public function prefetchTerms( array $entityIds, array $termTypes, array $languageCodes );

	/**
	 * Returns a term that was previously loaded by prefetchTerms.
	 *
	 * @param EntityId $entityId
	 * @param string $termType One of of TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION, TermTypes::TYPE_ALIAS
	 * @param string $languageCode
	 *
	 * @return string|false|null The term, or false of that term is known to not exist,
	 *         or null if the term was not yet requested via prefetchTerms().
	 */
	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode );

}
