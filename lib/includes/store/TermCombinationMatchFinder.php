<?php

namespace Wikibase;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Dating service for Terms.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface TermCombinationMatchFinder {

	/**
	 * Takes an array in which each element in array of of Term.
	 * These terms can be incomplete so the search is not restrained on some fields.
	 *
	 * Looks for terms of a single entity that has a matching term for each element in one of the array of Term.
	 * If a match is found, the terms for that entity are returned complete with entity id and entity type info.
	 * The result is thus either an empty array when no match is found or an array with Term elements of size
	 * equal to the provided array of Term that matched.
	 *
	 * $termType and $entityType can be provided as default constraints for terms not having these fields set.
	 *
	 * $excludeId and $excludeType can be used to exclude any terms for the entity that matches this info.
	 *
	 * @since 0.4
	 *
	 * @param array $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param EntityId|null $excludeId
	 *
	 * @return array
	 */
	public function getMatchingTermCombination( array $terms, $termType = null, $entityType = null, EntityId $excludeId = null );

}
