<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;

/**
 * A service interface for looking up entity terms.
 *
 * @note: A TermLookup cannot be used to determine whether an entity exists or not.
 *
 * @since 1.1
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface TermLookup {

	/**
	 * Gets the label of an Entity with the specified EntityId and language code.
	 *
	 * @since 2.0
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws TermLookupException for entity not found
	 * @return string|null
	 */
	public function getLabel( EntityId $entityId, $languageCode );

	/**
	 * Gets all labels of an Entity with the specified EntityId.
	 *
	 * The result will contain the entries for the requested languages, if they exist.
	 *
	 * @since 2.0
	 *
	 * @param EntityId $entityId
	 * @param string[] $languageCodes The list of languages to fetch
	 *
	 * @throws TermLookupException if the entity was not found (not guaranteed).
	 * @return string[] labels, keyed by language.
	 */
	public function getLabels( EntityId $entityId, array $languageCodes );

	/**
	 * Gets the description of an Entity with the specified EntityId and language code.
	 *
	 * @since 2.0
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws TermLookupException for entity not found
	 * @return string|null
	 */
	public function getDescription( EntityId $entityId, $languageCode );

	/**
	 * Gets all descriptions of an Entity with the specified EntityId.
	 *
	 * If $languages is given, the result will contain the entries for the
	 * requested languages, if they exist.
	 *
	 * @since 2.0
	 *
	 * @param EntityId $entityId
	 * @param string[] $languageCodes The list of languages to fetch
	 *
	 * @throws TermLookupException if the entity was not found (not guaranteed).
	 * @return string[] descriptions, keyed by language.
	 */
	public function getDescriptions( EntityId $entityId, array $languageCodes );

}
